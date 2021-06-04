<?php
	require_once("vendor/autoload.php");

	$pageUrl = cfg_get("pageUrl");
	$html = file_get_contents($pageUrl);
	
	phpQuery::newDocument($html);
	
	$links = pq(".wikitable")->find("a");
	
	$all = array();

	foreach($links as $link){
		$link = pq($link);
		if (!preg_match("/\[[0-9]+]/", $link->text())) {
			$all[] = array(
				"name" => $link->text(),
				"url"  => "https://ru.wikipedia.org" . $link->attr("href")
			);
		}
	}

	phpQuery::unloadDocuments();

	$instances = cfg_get("instances");
	
	for ($i=0; $i < $instances and $i < count($all); $i++) { 
		
		$cartoon = $all[$i];
		$html = file_get_contents($cartoon["url"]);
		phpQuery::newDocument($html);

		$cartoonInfo = array(
			"name" => $cartoon["name"],
			"name_en" => getNameEN()
		);

		if($cartoonInfo["name_en"] != "") {
			downloadImage(translit($cartoonInfo["name"]));
			$cartoonInfo["id"] = translit($cartoon["name"]);
			$cartoonInfo["continuance"] = getContinuance();
			$cartoonInfo["handle"] = getHandle();
			$cartoonInfo["year"] = getYear();
			$cartoonInfo["language"] = getLanguage();
			$cartoonInfo["country"] = getCountry();
			$cartoonInfo["studio"] = getStudio();
		}
		
		phpQuery::unloadDocuments();
		
		if($cartoonInfo["name_en"] != "") {
			echo 'parsing '.$cartoonInfo["name_en"].' . . . ' . "\r\n";
			$filePath = "./cartoons/".$cartoonInfo['id'].".scs";
			$text = $cartoonInfo["id"]. ' 
			<- concept_cartoon;
			<- sc_node_not_relation;
			=> nrel_main_idtf:
				['.$cartoonInfo["name"].'] (* <- lang_ru;;*);
				['.$cartoonInfo["name_en"].'] (* <- lang_en;;*);
			
			<-rrel_key_sc_element:...
			(*
				<-illustration;;
				=>nrel_main_idtf:
					[Рис. ('.$cartoonInfo["name"].')](*<-lang_ru;;*);
					[Pic. ('.$cartoonInfo["name_en"].')](*<-lang_en;;*);;
				<=nrel_sc_text_translation:...
				(*
					->"file://IMG/'.$cartoonInfo["id"].'.jpg"(*=>nrel_format:format_jpg;;*);;
				*);;
			*); ';

			if($cartoonInfo["language"] != "") {
			$text = $text . '
			=>nrel_original_language: '.$cartoonInfo["language"].';';
			}
			if($cartoonInfo["country"] != "") {
				$text = $text . '
			=>nrel_production_country: '.$cartoonInfo["country"].';';
			}
			if($cartoonInfo["studio"]){
				$text = $text . '
			<= nrel_production: '.$cartoonInfo["studio"].';';
			}

			$text = $text . '
			=>nrel_continuance:   ... 
			(* 
				<- concept_quantity;; 
				<= nrel_relevance: ... 
				(* 
					-> rrel_minute: '.$cartoonInfo["continuance"].'
					(* 
						<- concept_number;; 
					*);; 
				*);; 
			*);

			=>nrel_production_year:  ...
			(* 
				<- concept_quantity;; 
				<= nrel_relevance: ... 
				(* 
					-> rrel_year: '.$cartoonInfo["year"].'
					(* 
						<- concept_number;; 
					*);; 
				*);; 
			*); 
			';

			if ($cartoonInfo["handle"]) {
				$text .= '=>nrel_handle:  ...
				(* 
					<- concept_quantity;; 
					<= nrel_relevance: ... 
					(* 
						-> rrel_american_dollar: '.$cartoonInfo["handle"].'
						(* 
						<- concept_number;; 
						*);; 
					*);; 
				*);;
				';
			}

			$file = fopen($filePath, 'w');
			fwrite($file, $text);
			fclose($file);
		} else $instances++;
	}
?>	


<?php
  function translit($str) {
    $rus = array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я', ' ');
    $lat = array('A', 'B', 'V', 'G', 'D', 'E', 'E', 'Zh', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'C', 'Ch', 'Sh', 'Sch', '', 'Y', '', 'E', 'Yu', 'Ya', 'a', 'b', 'v', 'g', 'd', 'e', 'e', 'zh', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'sch', '', 'y', '', 'e', 'yu', 'ya', '_');
    return str_replace($rus, $lat, $str);
  }

  function cfg_get($option) {
	preg_match('/'.$option.' = \".*\"/', file_get_contents("config.cfg"), $value);
	$value =  $value[0];
	$value = preg_replace('/'.$option.' = \"/', "", $value);
	$value = substr($value, 0, -1);
	return $value;
  }

  function getContinuance() {
	$tags = pq(".infobox") -> find("tr") -> find('[data-wikidata-property-id="P2047"]');
	$text = strip_tags($tags);
	$text = preg_replace("/\[[0-9]+]/", "", $text);
	$minutes = filter_var($text, FILTER_SANITIZE_NUMBER_INT);
	return $minutes;
  }

  function downloadImage($name) {
	$url = pq(".infobox-image") -> find("img")-> attr("src");
	$url = 'https:' . $url;
	$path = './cartoons/IMG/'.$name.'.jpg';
	file_put_contents($path, file_get_contents($url));
  }

  function getNameEN() {
	$tags = pq(".infobox") -> find("tr") -> find('[data-wikidata-property-id="P1476"]');
	$text = strip_tags($tags);
	$text = preg_replace("/((англ)|(исп))\./", "", $text);
	return $text;
  }

  function getHandle(){
	$tags = pq(".infobox") -> find("tr") -> find('[data-wikidata-property-id="P2142"]');
	$text = htmlspecialchars(strip_tags($tags));
	$text = preg_replace("/\[[0-9]+]/", "", $text);
	if (strpos($text, '.'))
		$text = substr($text, 0, strpos($text, '.'));
	else if (strpos($text, ';')) 
		$text = substr($text, 0, strpos($text, ';'));
	$text = filter_var($text, FILTER_SANITIZE_NUMBER_INT);
	return (int)$text;
  }

  function getYear(){
	$tags = pq(".infobox") -> find("tr") -> find('[data-wikidata-property-id="P577"]');
	$text = strip_tags($tags);
	preg_match("/((19)|(20))\d\d/", $text, $text);
	return $text[0];
  }

  function getLanguage(){
	$tags = pq(".infobox") -> find("tr") -> find('[data-wikidata-property-id="P364"]');
	$text = strip_tags($tags);
	if($text == "английский") return "lang_en";
	if($text == "русский") return "lang_ru";
	return "";
  }

  function getCountry(){
	$tags = pq(".infobox") -> find("tr") -> find('[data-wikidata-property-id="P495"]') -> find("span");
	$text = strip_tags($tags);
	$text = preg_replace("/\[[0-9]+]/", "", $text);
	if(preg_match("/США/", $text)) 
		return "USA";
	return "";
  }

  function getStudio(){
	$tags = pq(".infobox") -> find("tr") -> find('[data-wikidata-property-id="P272"]');
	$text = strip_tags($tags);
	if(preg_match("/(Walt Disney)|(Дисне)/", $text))
		return "Disney";
	return "";
  }
?>





