<?php
/*
Plugin Name: Simple Newsletter Generator
Author: Tomi Ylä-Soininmäki
Author email: tomi.yla-soininmaki@fimnet.fi
Description: Tiedotuspostien automatisointi. Luo uuden artikkelimuodon, joista tiedotuspostit voi kasata.
*/
include( plugin_dir_path( __FILE__ ) . 'asetukset.php');


add_action( 'init', 'alusta_tiedotuspostityyppi' );
add_action( 'add_meta_boxes', 'lisaa_tiedotettava_metaboksit' );
add_action('save_post', 'tallenna_tiedotettavan_meta', 1, 2);
add_shortcode( 'tiedotusgeneraattori' , 'func_tiedotusgeneraattori');
add_action('admin_menu', 'lisaa_tiedotusgeneraattorisivu');



// ALUSTETAAN POSTAUSTYYPPI
function alusta_tiedotuspostityyppi() {
  register_post_type( 'tiedotettavat',
    array(
      'labels' => array(
        'name' => __( 'Tiedotettavat' ),
        'singular_name' => __( 'Tiedotettava' )
      ),
      'public' => false,
			'show_ui' => (current_user_can( 'manage_options' ) ? true : false),
      'has_archive' => false,
      'rewrite' => array('slug' => 'tiedotettavat'),
			'supports' => array( 'title', 'editor' ),
			'menu_position' => 5,
      'taxonomies' => array('tiedotusaihe'),
    )
  );
}


// TAKSONOMIAT

add_action('init', 'rekisteroi_tiedotusaihe_taksonomia');

function rekisteroi_tiedotusaihe_taksonomia() {
	
	$argumentit = array(
		'labels' => array(
			'name' => 'Aihe',
			'menu_name' => 'Aiheet',
		),
		'hierarchical' => 'true',
		'show_in_nav_menus' => false,
		//'sort' => true,
		'order' => 'ASC',
		'orderby' => 'term_order',
	);
	
	register_taxonomy('tiedotusaihe', 'tiedotettavat', $argumentit);
}

// TAKSONOMIEN JÄRJESTYS LÖYTYY ASETUKSISTA!


//	LISÄTÄÄN METABOKSIT
function lisaa_tiedotettava_metaboksit() {
	add_meta_box('tiedotettava_asetukset', 'Tiedotettavan asetukset', 'tiedotettava_asetukset_html', 'tiedotettavat', 'side', 'default');

}


// ASETUKSET-METABOKSI
function tiedotettava_asetukset_html() {


	global $post;
	
  echo '<input type="hidden" name="tiedotettavameta_noncename" id="eventmeta_noncename" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

  $vikapaiva = get_post_meta($post->ID, '_vikapaiva', true);
	$vikapaiva = ($vikapaiva == '' ? date(Ymd) + 7 : $vikapaiva);
  echo 'Vika päivä: <input type="text" name="_vikapaiva" value="' . $vikapaiva  . '" /><br />';

	/*
	$aihe = get_post_meta($post->ID, '_aihe', true);
	echo 'Aihe: <br />';
	foreach ($aihenimet as $avain => $aihenimi) {
		echo '<input type="radio" id="aiheid_'.$avain.'" name="_aihe" value="'.$avain.'" '.($aihe==$avain ? 'checked' : '').'><label for="aiheid_'.$avain.'">'.$aihenimi.'</label><br />';
	}
	echo '<br />' ;*/
	
	
	$tarkeys = get_post_meta($post->ID, '_tarkeys', true);
	$tarkeys = ($tarkeys == '' ? 50 : $tarkeys);
	echo 'Tärkeys (1-99): <br /><input type="text" name="_tarkeys" value="'. $tarkeys . '" /><br />' ;
	
	$lahetetaanko = get_post_meta($post->ID, '_lahetetaan', true);
	$lahetetaanko = ($lahetetaanko == '' ? 1 : $lahetetaanko);
	echo '<p><input type="checkbox" id="lahetetaanko" name="_lahetetaan" value="1" '.($lahetetaanko=='1' ? 'checked' : '').'/><label for="lahetetaanko">Lähetetäänkö</label><br /></p>';
}





// METABOKSIEN TALLENNUS

function tallenna_tiedotettavan_meta($post_id, $post) {
	if ( !wp_verify_nonce( $_POST['tiedotettavameta_noncename'], plugin_basename(__FILE__) ) || !current_user_can('edit_post',$post->ID)) {
		return $post->ID;
	}
	
	$tiedotettava_meta['_vikapaiva'] = $_POST['_vikapaiva'];
	$tiedotettava_meta['_tarkeys'] = $_POST['_tarkeys'];
	//$tiedotettava_meta['_aihe'] = $_POST['_aihe'];
	//$tiedotettava_meta['_jarjestys'] = 1000*$tiedotettava_meta['_aihe'] + $tiedotettava_meta['_tarkeys'];
	$tiedotettava_meta['_lahetetaan'] = ($_POST['_lahetetaan'] == '1' ? '1' : '0');
	/* Tähän pitää lisätä samalla logiikalla kaikki tallennettavat yksirivisesti */

	foreach ($tiedotettava_meta as $key => $arvo) {
		if ($post->post_type == 'revision') return; 
		$arvo = implode(',', (array)$arvo); //Tehdään mahdollisesta arraysta CSV tyyppinen array
		if (get_post_meta($post->ID, $key, FALSE)) {
			update_post_meta($post->ID, $key, $arvo);
		} else {
			add_post_meta($post->ID, $key, $arvo);
		}
		if(!$arvo) delete_post_meta($post->ID,$key);
	}
	
}







// TIEDOTETTAVIEN HAKEMINEN TIETOKANNASTA

function hae_tulevat_tiedotukset( $karsittu = 0 ) {
	$lahtien = date('Ymd');
	$args = array(
	'post_type' => 'tiedotettavat' ,
        'meta_key' => '_jarjestys' , 
        'orderby' => 'meta_value_num' ,
				'order' => 'ASC',
				'posts_per_page' => '1000',
				'meta_query' => array(
        	array(
            'key' => '_vikapaiva',
            'value' => $lahtien,
            'compare' => '>=',
       	 	) , 
        	($karsittu==1 ?
						array(
            'key' => '_lahetetaan',
            'value' => '1',
            'compare' => '=',
       	 	) : '')
				)
		
	);
	$kaikki_tiedotettavat = get_posts( $args );
	
	$kaikki_aiheet = get_terms('tiedotusaihe', array('hide_empty'=>false));
	
	$kaikki_postit = array();
	
	$asetukset = get_option( 'tiedotus-asetus');
	$aihejarjestys = $asetukset['tiedotusaihe'];
	
	foreach ($aihejarjestys as $slugi) {
		$termi = get_term_by('slug', $slugi, 'tiedotusaihe');
		$kaikki_postit[$termi->name] = array();
	}
	
  foreach ( $kaikki_tiedotettavat as $tiedotettava ) { 
		$aiheet = get_the_terms($tiedotettava->ID, 'tiedotusaihe');
		
		foreach ($aiheet as $aihe) {
			$kaikki_postit[$aihe->name][] = $tiedotettava;
		}
	}
	
	foreach ($kaikki_postit as $key => $aihelista) {
		if (empty($aihelista)) {
			unset($kaikki_postit[$key]);
		}
	}
	return $kaikki_postit;
}






// ENSIMMÄINEN SIVU eli KARSINTA

function tiedotettavien_valinta( $karsittavat_postit ) { 
	$aihenimet = get_terms('tiedotusaihe');
	ob_start();
	echo '<a href="/wp-admin/edit.php?post_type=tiedotettavat">Tietotettavien muokkaus</a><br />';
	echo '<form action="#" method="post">';
	foreach ($karsittavat_postit as $aihe => $aihelista) {
		echo '<h2 style="margin: 12pt 0">'.$aihe.'</h2>';
		foreach ($aihelista as $post) {
			$lahetetaanko = get_post_meta($post->ID, '_lahetetaan', true);
			echo '<input type="checkbox" id="checkid_'.$post->ID.'" name="checkid_'.$post->ID.'" '.($lahetetaanko=='1' ? 'checked' : '').'/> <label class="karsittava" for="checkid_'.$post->ID.'">'. $post->post_title . '</label><a href="'.get_edit_post_link( $post->ID ).'" style="float:right">Muokkaa</a><br />';
		}
	}
	echo '<br /><br /><input type="hidden" name="valittiinkojo" value="1" /><input type="submit" value="Valitse"/> </form>';
	return ob_get_clean();
}




// VARSINAINEN OHJELMA

function func_tiedotusgeneraattori() { 
	if ( !current_user_can( 'manage_options' )) { return $post->ID; } //on admin
	
	$karsittavat_postit = hae_tulevat_tiedotukset();
	
	if ( !isset($_POST['valittiinkojo']) && !isset($_POST['lahetys'])) {
		return tiedotettavien_valinta($karsittavat_postit);
	} 
	
	//Nyt on siis jo postdatassa karsitut!
	if (!isset($_POST['lahetys'])) {
		foreach ($karsittavat_postit as $aihe) {
			foreach ($aihe as $post) {
				$lahetettaisko = (isset($_POST['checkid_'.$post->ID])? '1': '0');
				update_post_meta($post->ID, '_lahetetaan', $lahetettaisko); // Tallennetaan lähetystieto
			}
		}
	}
	$karsitut_postit = hae_tulevat_tiedotukset(1);
	
	$asetukset = get_option( 'tiedotus-asetus');
	
	ob_start(); // ******************************************************************************************************  Sivuille tulostettava
	
	if (isset($_POST['lahetys']) && $_POST['lahetys']==1 && !isset($_POST['paivitys'])) { echo '<p style="font-weight:bold">Lähetetty!</p>'; }
	
	
	//Otsikkokenttä
	$oletusotsikko = $asetukset['otsikko'];
	$oletusotsikko = str_replace('{viikko}', date('W'), $oletusotsikko);
	$oletusotsikko = str_replace('{vuosi}', date('Y'), $oletusotsikko);
	$otsikko = (isset($_POST['otsikko']) ? $_POST['otsikko'] : $oletusotsikko  );
	echo '<p> <label for="otsikkokentta">Otsikko:</label><input id="otsikkokentta" form="lahetysform" type="text" width=100 name="otsikko" value="'.$otsikko.'" style="width: 100%"/> </p>'; 
	
	
	//Vastaanottajat
	
	$vastaanottajat = (isset($_POST['vastaanottajat']) ? $_POST['vastaanottajat'] : $asetukset['vastaanottajat'] );
	echo '<p> <label for="vastaanottajat">Vastaanottajat (Bcc), erottele pilkulla (,):</label><input id="vastaanottajat" form="lahetysform" type="text" name="vastaanottajat" value="'.$vastaanottajat.'" style="width: 100%"/> </p>'; 
	
	
	//Alkusanoja
	$alkusanat = (isset($_POST['alkusanat']) ? $_POST['alkusanat'] : $asetukset['alkusanat'] );
	$alkusanat = str_replace( '\"' , '"' , $alkusanat);
	echo '<p> <label for="alkusanat">Alkusanat:</label><textarea id="alkusanat" form="lahetysform" type="text" rows="5" name="alkusanat" style="width: 100%" >'.$alkusanat.'</textarea> </p>'."\r\n";
	$alkusanat = nl2br($alkusanat);
	echo '<hr>';
	
	ob_start(); // Viesti (kaikki viestiin päätyvä)
	echo '<div style="color:black;">';
	ob_start(); // Aiheet
	
	
	
	// Tehdään otsikot
	echo $alkusanat;
	$monesko = 1;
	foreach ($karsitut_postit as $aihe => $aihelista) { 
		echo '<h2 style="font-size:14pt; margin-left: 6pt; margin-top: 6pt; margin-bottom: 6pt">&nbsp;'.$aihe.'</h2>'."\r\n". '<ol start="'.$monesko.'">';
		foreach ($aihelista as $post) {
			$monesko++;
			echo '<li>'.$post->post_title . '</li>'."\r\n";
		}
		echo '</ol>'."\r\n";
	}
	$pelkataiheet = ob_get_contents();
	echo $asetukset['valiteksti'];
	
	
	
	// Tehdään sisällöt
	$monesko = 0;
	foreach ($karsitut_postit as $aihe => $aihelista) { 
		echo '<h2 style="font-size:14pt; margin-left: 0; margin-top: 12pt; margin-bottom: 6pt; font-weight: bold;">'.$aihe.'</h2>';
		foreach ($aihelista as $post) {
			$monesko++;
			echo '<span>'.$monesko.':  </span><div style="margin-left: 18pt; margin-top: 8pt; margin-bottom: 14pt; padding-left: 12pt; padding-bottom: 6pt; border-left: 1px solid black; border-bottom: 1px solid black;"> <p><span style="padding-bottom: 2pt; border-bottom: 1px solid #AAA; font-weight: bold;" >'.$post->post_title . '</span></p>';
			echo wpautop($post->post_content) .'</div>';
		}
	}
	echo $asetukset['allekirjoitus'];
	echo '</div>';
	$viesti = ob_get_contents(); // VIESTI LOPPUU
  
	
	if (isset($_POST['tallennapost'])) { // ************************************ Halutaanko tallentaa artikkelina
		if (get_cat_id('tiedotuspostit') == 0) {
			$my_cat = array('category_description' => 'Jo lähetetyt tiedotuspostit' , 'category_slug' => 'tiedotuspostit');
			wp_insert_term('tiedotuspostit', 'category' , $my_cat);
		}
		$kategoriaid = get_cat_id('tiedotuspostit');
		$argumentit = array(
			'post_title' => $otsikko ,
			'post_content' => $viesti ,
			'post_status' => 'publish' ,
			'post_category' => array($kategoriaid)
    );
		wp_insert_post($argumentit);
	}
	
	if (isset($_POST['lahetys']) && $_POST['lahetys']==1 && !isset($_POST['paivitys'])) { // Jos pyydetty jo lähettämään niin lähetä
		$liite = (isset($_POST['liite']) ? $_POST['liite'] : NULL);
		if (laheta_tiedotus($viesti, $otsikko, $vastaanottajat, $liite)) {
			echo '<br /><br /> Viesti lähtenyt onnistuneesti!';
		} else {
			echo '<br /><br /> Jokin meni jo lähetysvaiheessa pieleen. Jännää. Avaa lähdekoodi ja kopioi viestiosa sieltä ja lähetä oman sähköpostisi kautta?';
		}
	} else { // Lähetyspainike
		$liite = (isset($_POST['liite'])? $_POST['liite'] : NULL);
		echo '<br /><br /><form onsubmit="return confirm(\'Suoritetaanko toiminnot?\');" id="lahetysform" action="#" method="post" enctype="multipart/form-data">
<input type="hidden" name="lahetys" value="1" />

<label for="liite">Liitteen id:</label><input type="text" id="liite" name="liite" /><br />
<label for="pelkkapaivitys">Esikatselu, ei vielä lähetystä:</label><input type="checkbox" name="paivitys" id="pelkkapaivitys" value="1" /><br />
<label for="tallennapost">Tallenna artikkelina:</label><input type="checkbox" name="tallennapost" id="tallennapost" value="1" /><br /><input type="submit" value="Suorita" /></form>';
	}
	
	return ob_get_clean();
}

function laheta_tiedotus($viesti, $otsikko, $vastaanottajat, $liite=NULL ) {
		$asetukset = get_option( 'tiedotus-asetus');
		
	  $liite = ($liite==NULL?NULL : get_attached_file($liite));
	
		$kenelle = $asetukset['lahettaja'];
		
		$otsakkeet  = 'MIME-Version: 1.0' . "\r\n";
		$otsakkeet .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
		
		$otsakkeet .= 'From: '.$kenelle."\r\n";
		$otsakkeet .= 'Bcc: '.$vastaanottajat."\r\n";
		
		return wp_mail($kenelle, $otsikko , $viesti, $otsakkeet, $liite );
		
	
}


// LISÄTÄÄN TIEDOTUSGENERAATTORISIVU

function lisaa_tiedotusgeneraattorisivu()   {
	add_submenu_page( 'edit.php?post_type=tiedotettavat', 'Tiedotusgeneraattori' , 'Tiedotusgeneraattori', 'activate_plugins', 'tiedotusgeneraattori', 'tiedotusgeneraattorisivu' );
}


// TIEDOTUSGENERAATTORISIVUN SISÄLTÖ

function tiedotusgeneraattorisivu() {
	echo '<div class="wrap" style="background-color: white; max-width: 1000px;"><style>#wpcontent {background-color: white;}</style>';
	echo func_tiedotusgeneraattori();
	echo '</div>';	
}
