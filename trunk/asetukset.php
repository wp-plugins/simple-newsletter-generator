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
			add_submenu_page( 'edit.php?post_type=tiedotettavat', 'Tiedotusasetukset' , __('Newsletter settings' ,'tiedotus'), 'activate_plugins', 'tiedotuksen-asetukset', array( $this, 'create_admin_page' ) );
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
					<?php _e('Plugin by:' ,'tiedotus'); ?> <a href="mailto:tomi.yla-soininmaki@fimnet.fi">Tomi Ylä-Soininmäki</a>
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
            __('Mail and recipients' ,'tiedotus'), // Title
            array( $this, 'print_lahetys_info' ), // Callback
            'tiedotuksen-asetukset' // Page
        );        

        add_settings_field(
            'lahettaja', 
            __('Sender' ,'tiedotus'), 
            array( $this, 'lahettaja' ), 
            'tiedotuksen-asetukset', 
            'lahetystiedot'
        );  

        add_settings_field(
            'aiheet', 
            __('Order of subjects' ,'tiedotus'), 
            array( $this, 'aiheet' ), 
            'tiedotuksen-asetukset', 
            'lahetystiedot'
        );            

        add_settings_field(
            'otsikko', 
            __('Default subject/title' ,'tiedotus'), 
            array( $this, 'otsikko' ), 
            'tiedotuksen-asetukset', 
            'lahetystiedot'
        );  

        add_settings_field(
            'vastaanottajat', // ID
            __('Default recipients (Bcc), separate with commas ", "' ,'tiedotus'), // Title 
            array( $this, 'vastaanottajat' ), // Callback
            'tiedotuksen-asetukset', // Page
            'lahetystiedot' // Section           
        );     

        add_settings_field(
            'alkusanat', // ID
            __('Default intro text / header' ,'tiedotus'), // Title 
            array( $this, 'alkusanat' ), // Callback
            'tiedotuksen-asetukset', // Page
            'lahetystiedot' // Section           
        );    

        add_settings_field(
            'valiteksti', // ID
            __('Subject-content separator text (html)' ,'tiedotus'), // Title 
            array( $this, 'valiteksti' ), // Callback
            'tiedotuksen-asetukset', // Page
            'lahetystiedot' // Section           
        );    

        add_settings_field(
            'allekirjoitus', // ID
            __('Signature (html)' ,'tiedotus'), // Title 
            array( $this, 'allekirjoitus' ), // Callback
            'tiedotuksen-asetukset', // Page
            'lahetystiedot' // Section           
        );    

        add_settings_field(
            'sisalto_html', // ID
            __('Format the content box (html)' ,'tiedotus'), // Title 
            array( $this, 'sisalto_html' ), // Callback
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

        if( isset( $input['sisalto_html'] ) )
            $new_input['sisalto_html'] = $input['sisalto_html'];

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
        print __('Email settings:' ,'tiedotus').' <br>';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function aiheet()
    {
			$kaikki_aiheet = get_terms('tiedotusaihe', array('hide_empty'=>false));
			$maara = count($kaikki_aiheet);
			$koodillalisatyt = array();
			for ($i=0 ; $i<count($kaikki_aiheet) ; $i++) {
				echo ($i+1).': <select name="tiedotus-asetus[tiedotusaihe'.$i.']">';
        $maarittamaton = true;
				foreach ($kaikki_aiheet as $aihe) {
          if ($aihe->slug == $this->options['tiedotusaihe'][$i]) $maarittamaton = false;
        }
        foreach ($kaikki_aiheet as $aihe) {
          echo '<option value="'.$aihe->slug.'"';
          if ($aihe->slug == $this->options['tiedotusaihe'][$i]) {
            echo ' selected ' ;
          } else {
            if ($maarittamaton && !in_array($aihe->slug,$this->options['tiedotusaihe']) && !in_array($aihe->slug,$koodillalisatyt)) {
              echo ' selected ';
              $koodillalisatyt[] = $aihe->slug;
              $maarittamaton = false;
            }
          }
          echo '>'.$aihe->name.'</option>';
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
            '<input type="text" id="otsikko" name="tiedotus-asetus[otsikko]" value="%s" size=100 /><br />'.__('{week}, {year} produce corresponding numbers' ,'tiedotus'),
            isset( $this->options['otsikko'] ) ? esc_attr( $this->options['otsikko']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function lahettaja()
    {
        printf(
            '<input type="text" id="lahettaja" name="tiedotus-asetus[lahettaja]" value="%s" size=100 /><br /><i>'.__('Matt Example &lt;email&#64;example.com&gt;' ,'tiedotus').'</i>',
            isset( $this->options['lahettaja'] ) ? esc_attr( $this->options['lahettaja']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function vastaanottajat()
    {
        printf(
            '<textarea id="vastaanottajat" cols=100 rows=4 name="tiedotus-asetus[vastaanottajat]">%s</textarea>',
            isset( $this->options['vastaanottajat'] ) ? esc_attr( $this->options['vastaanottajat']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function alkusanat()
    {
        printf(
            '<textarea id="alkusanat" cols=100 rows=4 name="tiedotus-asetus[alkusanat]">%s</textarea><br />'.__('html without br-tags (automatic line breaks)' ,'tiedotus'), 
            isset( $this->options['alkusanat'] ) ? esc_attr( $this->options['alkusanat']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function allekirjoitus()
    {
        printf(
            '<textarea id="allekirjoitus" cols=100 rows=8 name="tiedotus-asetus[allekirjoitus]">%s</textarea>',
            isset( $this->options['allekirjoitus'] ) ? esc_attr( $this->options['allekirjoitus']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function sisalto_html()
    {
        printf(
            '<textarea id="sisalto_html" cols=100 rows=10 name="tiedotus-asetus[sisalto_html]" placeholder="'.htmlspecialchars(tiedotuksen_oletus_sisaltomuotoilu()).'">%s</textarea><br />'.__('html, {title} produces post title and {content} content. Use inline css.' ,'tiedotus'),
            isset( $this->options['sisalto_html'] ) ? esc_attr( $this->options['sisalto_html']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function valiteksti()
    {
        printf(
            '<textarea id="valiteksti" cols=100 rows=4 name="tiedotus-asetus[valiteksti]" placeholder="'.htmlspecialchars('<hr />').'">%s</textarea>',
            isset( $this->options['valiteksti'] ) ? esc_attr( $this->options['valiteksti']) : ''
        );
    }
}

if( is_admin() ) $tiedotusasetukset = new TiedotusAsetukset();