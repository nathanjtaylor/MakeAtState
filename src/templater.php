<?php


class Templater
{
    const HTTP_OK 			= 200;
    const HTTP_MOVED_PERMANETLY 	= 301;
    const HTTP_FOUND 		= 302;
    const HTTP_BAD_REQUEST		= 400;
    const HTTP_FORBIDDEN		= 403;
    const HTTP_FILE_NOT_FOUND	= 404;
    const HTTP_INTERNAL_SERVER_ERROR= 500;
    
    // twig variable
    private $twig;
    // Varibales passed to the template file
    private $aVariables = array();

    // Varibales passed to the block
    private $aBlockVariables =array();

    // Name of the twig template file
    private $sTemplateName;

    // Name of the twig block
    private $sBlockName;

    // Dir of the templates
    private $TemplateDir;

    
    /**
    * Init the templating class
    * @param $sTemplateName Optionally , set the name of the template
    * @param $sTemplateDir Optionally , set the dir of the template

    */
    public function __construct($sTemplateName=null)
    {
        $this->sTemplateName = $sTemplateName;
        $this->sTemplateDir = dirname(__DIR__).'/templates/default/';
    }

    /** Sets the template name
    * @param string $sTemplName : name of the template file

    */

    public function setTemplate($sTemplName)
    {
        $this->sTemplateName = $sTemplName;
    }

    /**
    * Sets the variable to pass to a template
    * @param string sName Name of the varibale
    * @param mixed $sValue Value of the variable

    */
    public function setVariables($sName, $sValue)
    {
        $this->aVariables[$sName] = $sValue;
    }
    /**
    * Sets the variable to pass to a template
    * @param string sName Name of the varibale
    * @param mixed $sValue Value of the variable

    */
    public function setBlockVariables($sName, $sValue)
    {
        $this->aBlockVariables[$sName] = $sValue;
    }
    /**
    * Sets the block name to render
    * @param $sBlockName : Name of the block to render
    */
    public function setBlock($sBlockName)
    {
        $this->aBlockName =  $sBlockName;
    }

    /**
    * Returns the current $aVariables Array

    */
    public function getVariables()
    {
        return $this->aVariables;
    }
    


    public function generate()
    {
        $loader = new Twig_Loader_Filesystem($this->sTemplateDir);
        //TODO cache dir
        if (empty($this->twig)) {
            $this->twig = new Twig_Environment($loader);
        }
        $template = $this->twig->load($this->sTemplateName);
        
        # check if the template has any variables that need to be passed
        if (!empty($this->aVariables)) {
            $template->display($this->aVariables);
        } else {
            $template->display();
        }
    }
    
    /**
    * Generate the block using twig
    */
    public function generateBlock()
    {
        $loader = new Twig_Loader_Filesystem($this->sTemplateDir);
        if (empty($this->twig)) {
            $this->twig = new Twig_Environment($loader);
        }
        $template = $this->twig->load($this->sTemplateName);
        $block = $template->renderBlock($this->aBlockName, $this->aBlockVariables);
        return $block;
    }

    /**
    * Function to add a twig filter
    * @param string $filter_name : Name of the filter used in the template
    */
    public function addTwigFilter($filter_name)
    {
        $loader = new Twig_Loader_Filesystem($this->sTemplateDir);
        $filter = "";
        if (empty($this->twig)) {
            $this->twig = new Twig_Environment($loader);
        }
        if ($filter_name == 'displayExtentions') {
            $filter = new Twig_Filter('displayExtentions', [$this, 'displayExtentions']);
        } else {
            $filter = new Twig_Filter('displayExtentions', [$this, 'displayDefaultValue']);
        }
                
        $this->twig->addFilter($filter);
    }
   
    /**
    * Function that displays the allowed extensions on a user friendly format
    * @param string $value : Value passed from the twig template
    */
    public function displayExtentions($value)
    {
        $display_exts = "";
        if (!empty($value)) {
            $stored_extensions = unserialize($value);
            foreach ($stored_extensions as $ext) {
                $display_exts .= (end($stored_extensions) !== $ext) ? $ext.', ': $ext;
            }
        }
        return $display_exts;
    }

    /**
    * Function that displays the allowed extensions on a user friendly format
    * @param string $value : Value passed from the twig template
    */
    public function displayDefaultValue($value)
    {
        return $value;
    }
}
