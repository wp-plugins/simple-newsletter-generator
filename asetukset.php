<?php



class TiedotusAsetukset
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'lisaa_tiedotusasetussivu' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function lisaa_tiedotusasetussivu()
    {
        // This page will be under "Tiedotettavat"
			add_submenu_page( 'edit.php?post_type=tiedotettavat', 'Tiedotusasetukset' , 'Tiedotuksen asetukset', 'activate_plugins', 'tiedotuksen-asetukset', array( $this, 'create_admin_page' ) );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'tiedotus-asetus' );
        ?>
        <div class="wrap">
            <h2>Tiedotuksen asetuksia</h2>           
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'tiedotus-ryhma' );   
                do_settings_sections( 'tiedotuksen-asetukset' );
                submit_button(); 
            ?>
            </form>
					Pluginin tekijä: <a href="mailto:tomi.yla-soininmaki@fimnet.fi">Tomi Ylä-Soininmäki</a>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'tiedotus-ryhma', // Option group
            'tiedotus-asetus', // Option name
            array( $this, 'sanitize_tiedotusasetukset' ) // Sanitize
        );

        add_settings_section(
            'lahetystiedot', // ID
            'Lähetys ja vastaanottajat', // Title
            array( $this, 'print_lahetys_info' ), // Callback
            'tiedotuksen-asetukset' // Page
        );        

        add_settings_field(
            'aiheet', 
            'Aiheiden järjestys', 
            array( $this, 'aiheet' ), 
            'tiedotuksen-asetukset', 
            'lahetystiedot'
        );            

        add_settings_field(
            'otsikko', 
            'Oletusotsikko', 
            array( $this, 'otsikko' ), 
            'tiedotuksen-asetukset', 
            'lahetystiedot'
        );  

        add_settings_field(
            'lahettaja', 
            'Lähettäjä', 
            array( $this, 'lahettaja' ), 
            'tiedotuksen-asetukset', 
            'lahetystiedot'
        );  

        add_settings_field(
            'vastaanottajat', // ID
            'Oletusvastaanottajat (Bcc), erottele pilkulla', // Title 
            array( $this, 'vastaanottajat' ), // Callback
            'tiedotuksen-asetukset', // Page
            'lahetystiedot' // Section           
        );     

        add_settings_field(
            'alkusanat', // ID
            'Alun oletusteksti', // Title 
            array( $this, 'alkusanat' ), // Callback
            'tiedotuksen-asetukset', // Page
            'lahetystiedot' // Section           
        );    

        add_settings_field(
            'valiteksti', // ID
            'Välissä oleva teksti (html)', // Title 
            array( $this, 'valiteksti' ), // Callback
            'tiedotuksen-asetukset', // Page
            'lahetystiedot' // Section           
        );    

        add_settings_field(
            'allekirjoitus', // ID
            'Allekirjoitus (html)', // Title 
            array( $this, 'allekirjoitus' ), // Callback
            'tiedotuksen-asetukset', // Page
            'lahetystiedot' // Section           
        );    
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize_tiedotusasetukset( $input )
    {
        $new_input = array();
        if( isset( $input['vastaanottajat'] ) )
            $new_input['vastaanottajat'] = sanitize_text_field( $input['vastaanottajat'] );

        if( isset( $input['lahettaja'] ) )
            $new_input['lahettaja'] = $input['lahettaja'] ;

        if( isset( $input['otsikko'] ) )
            $new_input['otsikko'] = sanitize_text_field( $input['otsikko'] );

        if( isset( $input['allekirjoitus'] ) )
            $new_input['allekirjoitus'] = $input['allekirjoitus'];

        if( isset( $input['valiteksti'] ) )
            $new_input['valiteksti'] = $input['valiteksti'];

        if( isset( $input['alkusanat'] ) )
            $new_input['alkusanat'] = $input['alkusanat'];
			
			$kaikki_aiheet = get_terms('tiedotusaihe', array('hide_empty'=>false));
			$maara = count($kaikki_aiheet);
			
			for ($i=0 ; $i<count($kaikki_aiheet) ; $i++) {
				$aiheslugi = $input['tiedotusaihe'.$i];
				if ($aiheslugi) {
					$new_input['tiedotusaihe'][$i] = $aiheslugi;
				}
			}
			return $new_input;
		}

    /** 
     * Print the Section text
     */
    public function print_lahetys_info()
    {
        print 'Lähetyksen asetukset: <br>';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function aiheet()
    {
			$kaikki_aiheet = get_terms('tiedotusaihe', array('hide_empty'=>false));
			$maara = count($kaikki_aiheet);
			
			for ($i=0 ; $i<count($kaikki_aiheet) ; $i++) {
				echo ($i+1).': <select name="tiedotus-asetus[tiedotusaihe'.$i.']">';
				foreach ($kaikki_aiheet as $aihe) {
					echo '<option value="'.$aihe->slug.'"'.($aihe->slug == $this->options['tiedotusaihe'][$i]?' selected ' : '').'>'.$aihe->name.'</option>';
				}
				echo '</select><br />';
			}
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function otsikko()
    {
        printf(
            '<input type="text" id="otsikko" name="tiedotus-asetus[otsikko]" value="%s" size=100 /><br />{viikko}, {vuosi} tuottavat kyseiset numerot',
            isset( $this->options['otsikko'] ) ? esc_attr( $this->options['otsikko']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function lahettaja()
    {
        printf(
            '<input type="text" id="lahettaja" name="tiedotus-asetus[lahettaja]" value="%s" size=100 /><br />Esimerkkinimi &lt;osoite&#64;example.com&gt;',
            isset( $this->options['lahettaja'] ) ? esc_attr( $this->options['lahettaja']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function vastaanottajat()
    {
        printf(
            '<textarea id="vastaanottajat" cols=100 rows=10 name="tiedotus-asetus[vastaanottajat]">%s</textarea>',
            isset( $this->options['vastaanottajat'] ) ? esc_attr( $this->options['vastaanottajat']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function alkusanat()
    {
        printf(
            '<textarea id="alkusanat" cols=100 rows=10 name="tiedotus-asetus[alkusanat]">%s</textarea><br />html ilman br-koodeja (rivinvaihdot toimivat sellaisenaan)', 
            isset( $this->options['alkusanat'] ) ? esc_attr( $this->options['alkusanat']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function allekirjoitus()
    {
        printf(
            '<textarea id="allekirjoitus" cols=100 rows=10 name="tiedotus-asetus[allekirjoitus]">%s</textarea>',
            isset( $this->options['allekirjoitus'] ) ? esc_attr( $this->options['allekirjoitus']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function valiteksti()
    {
        printf(
            '<textarea id="valiteksti" cols=100 rows=10 name="tiedotus-asetus[valiteksti]">%s</textarea>',
            isset( $this->options['valiteksti'] ) ? esc_attr( $this->options['valiteksti']) : ''
        );
    }
}

if( is_admin() ) $tiedotusasetukset = new TiedotusAsetukset();