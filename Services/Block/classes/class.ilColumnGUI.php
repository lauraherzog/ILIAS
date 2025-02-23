<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

define("IL_COL_LEFT", "left");
define("IL_COL_RIGHT", "right");
define("IL_COL_CENTER", "center");

define("IL_SCREEN_SIDE", "");
define("IL_SCREEN_CENTER", "center");
define("IL_SCREEN_FULL", "full");

/**
* Column user interface class. This class is used on the personal desktop,
* the info screen class and witin container classes.
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ilCtrl_IsCalledBy ilColumnGUI: ilCalendarGUI
* @ilCtrl_Calls ilColumnGUI:
*/
class ilColumnGUI
{
    /**
     * @var ilCtrl
     */
    protected $ctrl;

    /**
     * @var ilLanguage
     */
    protected $lng;

    /**
     * @var ilObjUser
     */
    protected $user;

    /**
     * @var ilTemplate
     */
    protected $tpl;

    /**
     * @var ilBrowser
     */
    protected $browser;

    /**
     * @var ilSetting
     */
    protected $settings;

    protected $side = IL_COL_RIGHT;
    protected $type;
    protected $enableedit = false;
    protected $repositorymode = false;
    protected $repositoryitems = array();
    
    // all blocks that are repository objects
    protected $rep_block_types = array("feed","poll");
    protected $block_property = array();
    protected $admincommands = false;

    /**
     * @var ilAdvancedSelectionListGUI
     */
    protected $action_menu;
    
    //
    // This two arrays may be replaced by some
    // xml or other magic in the future...
    //
    
    protected static $locations = array(
        "ilNewsForContextBlockGUI" => "Services/News/",
        "ilCalendarBlockGUI" => "Services/Calendar/",
        "ilPDCalendarBlockGUI" => "Services/Calendar/",
        "ilPDTasksBlockGUI" => "Services/Tasks/",
        "ilPDMailBlockGUI" => "Services/Mail/",
        "ilPDSelectedItemsBlockGUI" => "Services/Dashboard/ItemsBlock/",
        "ilPDNewsBlockGUI" => "Services/News/",
        "ilExternalFeedBlockGUI" => "Modules/ExternalFeed/",
        "ilPDExternalFeedBlockGUI" => "Services/Feeds/",
        'ilPollBlockGUI' => 'Modules/Poll/',
        'ilClassificationBlockGUI' => 'Services/Classification/',
        "ilPDStudyProgrammeSimpleListGUI" => "Modules/StudyProgramme/",
        "ilPDStudyProgrammeExpandableListGUI" => "Modules/StudyProgramme/",
    );
    
    protected static $block_types = array(
        "ilPDMailBlockGUI" => "pdmail",
        "ilPDTasksBlockGUI" => "pdtasks",
        "ilPDNewsBlockGUI" => "pdnews",
        "ilNewsForContextBlockGUI" => "news",
        "ilCalendarBlockGUI" => "cal",
        "ilPDCalendarBlockGUI" => "pdcal",
        "ilExternalFeedBlockGUI" => "feed",
        "ilPDExternalFeedBlockGUI" => "pdfeed",
        "ilPDSelectedItemsBlockGUI" => "pditems",
        'ilPollBlockGUI' => 'poll',
        'ilClassificationBlockGUI' => 'clsfct',
        "ilPDStudyProgrammeSimpleListGUI" => "prgsimplelist",
        "ilPDStudyProgrammeExpandableListGUI" => "prgexpandablelist",
    );
    
        
    protected $default_blocks = array(
        "cat" => array(
            "ilNewsForContextBlockGUI" => IL_COL_RIGHT,
            "ilClassificationBlockGUI" => IL_COL_RIGHT
            ),
        "crs" => array(
            "ilNewsForContextBlockGUI" => IL_COL_RIGHT,
            "ilCalendarBlockGUI" => IL_COL_RIGHT,
            "ilClassificationBlockGUI" => IL_COL_RIGHT
            ),
        "grp" => array(
            "ilNewsForContextBlockGUI" => IL_COL_RIGHT,
            "ilCalendarBlockGUI" => IL_COL_RIGHT,
            "ilClassificationBlockGUI" => IL_COL_RIGHT
            ),
        "frm" => array("ilNewsForContextBlockGUI" => IL_COL_RIGHT),
        "root" => array(),
        "info" => array(
            "ilNewsForContextBlockGUI" => IL_COL_RIGHT),
        "pd" => array(
            "ilPDTasksBlockGUI" => IL_COL_RIGHT,
            "ilPDCalendarBlockGUI" => IL_COL_RIGHT,
            "ilPDNewsBlockGUI" => IL_COL_RIGHT,
            "ilPDStudyProgrammeSimpleListGUI" => IL_COL_CENTER,
            "ilPDStudyProgrammeExpandableListGUI" => IL_COL_CENTER,
            "ilPDSelectedItemsBlockGUI" => IL_COL_CENTER,
            "ilPDMailBlockGUI" => IL_COL_RIGHT
            )
        );

    // these are only for pd blocks
    // other blocks are rep objects now
    protected $custom_blocks = array(
        "cat" => array(),
        "crs" => array(),
        "grp" => array(),
        "frm" => array(),
        "root" => array(),
        "info" => array(),
        "fold" => array(),
        "pd" => array()
    );
    /*
        "pd" => array("ilPDExternalFeedBlockGUI")
        );*/

    // check global activation for these block types
    // @todo: add calendar
    protected $check_global_activation =
        array("news" => true,
            "cal" => true,
            "pdcal" => true,
            "pdnews" => true,
            "pdtag" => true,
            "pdmail" => true,
            "pdtasks" => true,
            "tagcld" => true,
            "clsfct" => true);
            
    protected $check_nr_limit =
        array("pdfeed" => true);

    /**
    * Constructor
    *
    * @param
    */
    public function __construct($a_col_type = "", $a_side = "", $use_std_context = false)
    {
        global $DIC;

        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->user = $DIC->user();
        $this->tpl = $DIC["tpl"];
        $this->browser = $DIC["ilBrowser"];
        $this->settings = $DIC->settings();
        $this->setColType($a_col_type);
        $this->setSide($a_side);

        $this->dash_side_panel_settings = new ilDashboardSidePanelSettingsRepository();
    }

    /**
     *
     * Adds location information of the custom block gui
     *
     * @access	public
     * @static
     * @param	string	The name of the custom block gui class
     * @param	string	The path of the custom block gui class
     *
     */
    public static function addCustomBlockLocation($className, $path)
    {
        self::$locations[$className] = $path;
    }

    /**
     *
     * Adds the block type of the custom block gui
     *
     * @access	public
     * @static
     * @param	string	The name of the custom block gui class
     * @param	string	The identifier (block type) of the custom block gui
     *
     */
    public static function addCustomBlockType($className, $identifier)
    {
        self::$block_types[$className] = $identifier;
    }

    /**
    * Get Column Side of Current Command
    *
    * @return	string	Column Side
    */
    public static function getCmdSide()
    {
        return $_GET["col_side"];
    }

    /**
    * Set Column Type.
    *
    * @param	string	$a_coltype	Column Type
    */
    public function setColType($a_coltype)
    {
        $this->coltype = $a_coltype;
    }

    /**
    * Get Column Type.
    *
    * @return	string	Column Type
    */
    public function getColType()
    {
        return $this->coltype;
    }

    /**
    * Set Side IL_COL_LEFT | IL_COL_RIGHT.
    *
    * @param	string	$a_side	Side IL_COL_LEFT | IL_COL_RIGHT
    */
    public function setSide($a_side)
    {
        $this->side = $a_side;
    }

    /**
    * Get Side IL_COL_LEFT | IL_COL_RIGHT.
    *
    * @return	string	Side IL_COL_LEFT | IL_COL_RIGHT
    */
    public function getSide()
    {
        return $this->side;
    }

    /**
    * Set EnableEdit.
    *
    * @param	boolean	$a_enableedit	EnableEdit
    */
    public function setEnableEdit($a_enableedit)
    {
        $this->enableedit = $a_enableedit;
    }

    /**
    * Get EnableEdit.
    *
    * @return	boolean	EnableEdit
    */
    public function getEnableEdit()
    {
        return $this->enableedit;
    }

    /**
    * Set RepositoryMode.
    *
    * @param	boolean	$a_repositorymode	RepositoryMode
    */
    public function setRepositoryMode($a_repositorymode)
    {
        $this->repositorymode = $a_repositorymode;
    }

    /**
    * Get RepositoryMode.
    *
    * @return	boolean	RepositoryMode
    */
    public function getRepositoryMode()
    {
        return $this->repositorymode;
    }

    /**
    * Set Administration Commmands.
    *
    * @param	boolean	$a_admincommands	Administration Commmands
    */
    public function setAdminCommands($a_admincommands)
    {
        $this->admincommands = $a_admincommands;
    }

    /**
    * Get Administration Commmands.
    *
    * @return	boolean	Administration Commmands
    */
    public function getAdminCommands()
    {
        return $this->admincommands;
    }

    /**
    * Get Screen Mode for current command.
    */
    public static function getScreenMode()
    {
        global $DIC;

        $ilCtrl = $DIC->ctrl();

        if ($ilCtrl->getCmdClass() == "ilcolumngui") {
            switch ($ilCtrl->getCmd()) {
                case "addBlock":
                    return IL_SCREEN_CENTER;
            }
        }

        $cur_block_type = "";
        if (isset($_GET["block_type"]) && $_GET["block_type"]) {
            $cur_block_type = $_GET["block_type"];
        } elseif (isset($_POST["block_type"])) {
            $cur_block_type = $_POST["block_type"];
        }

        if ($class = array_search($cur_block_type, self::$block_types)) {
            include_once("./" . self::$locations[$class] . "classes/" .
                "class." . $class . ".php");
            return call_user_func(array($class, 'getScreenMode'));
        }

        return IL_SCREEN_SIDE;
    }
    
    /**
    * This function is supposed to be used for block type specific
    * properties, that should be passed to ilBlockGUI->setProperty
    *
    * @param	string	$a_property		property name
    * @param	string	$a_value		property value
    */
    public function setBlockProperty($a_block_type, $a_property, $a_value)
    {
        $this->block_property[$a_block_type][$a_property] = $a_value;
    }
    
    public function getBlockProperties($a_block_type)
    {
        return $this->block_property[$a_block_type];
    }

    public function setAllBlockProperties($a_block_properties)
    {
        $this->block_property = $a_block_properties;
    }

    /**
    * Set Repository Items.
    *
    * @param	array	$a_repositoryitems	Repository Items
    */
    public function setRepositoryItems($a_repositoryitems)
    {
        $this->repositoryitems = $a_repositoryitems;
    }

    /**
    * Get Repository Items.
    *
    * @return	array	Repository Items
    */
    public function getRepositoryItems()
    {
        return $this->repositoryitems;
    }

    /**
    * execute command
    */
    public function executeCommand()
    {
        $ilCtrl = $this->ctrl;
        
        $ilCtrl->setParameter($this, "col_side", $this->getSide());
        //$ilCtrl->saveParameter($this, "col_side");

        $next_class = $ilCtrl->getNextClass();
        $cmd = $ilCtrl->getCmd("getHTML");

        $cur_block_type = ($_GET["block_type"])
            ? $_GET["block_type"]
            : $_POST["block_type"];

        if ($next_class != "") {
            // forward to block
            if ($gui_class = array_search($cur_block_type, self::$block_types)) {
                include_once("./" . self::$locations[$gui_class] . "classes/" .
                    "class." . $gui_class . ".php");
                $ilCtrl->setParameter($this, "block_type", $cur_block_type);
                $block_gui = new $gui_class();
                $block_gui->setProperties($this->block_property[$cur_block_type]);
                $block_gui->setRepositoryMode($this->getRepositoryMode());
                $block_gui->setEnableEdit($this->getEnableEdit());
                $block_gui->setAdminCommands($this->getAdminCommands());

                if (in_array($gui_class, $this->custom_blocks[$this->getColType()]) ||
                    in_array($cur_block_type, $this->rep_block_types)) {
                    $block_class = substr($gui_class, 0, strlen($gui_class) - 3);
                    include_once("./" . self::$locations[$gui_class] . "classes/" .
                        "class." . $block_class . ".php");
                    $app_block = new $block_class($_GET["block_id"]);
                    $block_gui->setBlock($app_block);
                }
                $html = $ilCtrl->forwardCommand($block_gui);
                $ilCtrl->setParameter($this, "block_type", "");
                
                return $html;
            }
        } else {
            return $this->$cmd();
        }
    }

    /**
    * Get HTML for column.
    */
    public function getHTML()
    {
        $ilCtrl = $this->ctrl;
        
        $ilCtrl->setParameter($this, "col_side", $this->getSide());
        
        $this->tpl = new ilTemplate("tpl.column.html", true, true, "Services/Block");
        $this->determineBlocks();
        $this->showBlocks();

        if ($this->getEnableEdit() || !$this->getRepositoryMode()) {
            $this->addHiddenBlockSelector();
        }

        return $this->tpl->get();
    }
    
    /**
    * Show blocks.
    */
    public function showBlocks()
    {
        $ilCtrl = $this->ctrl;
        $lng = $this->lng;
        $ilUser = $this->user;

        $i = 1;
        $sum_moveable = count($this->blocks[$this->getSide()]);

        foreach ($this->blocks[$this->getSide()] as $block) {
            if ($ilCtrl->getContextObjType() != "user" ||
                ilBlockSetting::_lookupDetailLevel(
                    $block["type"],
                    $ilUser->getId(),
                    $block["id"]
                ) > 0) {
                $gui_class = $block["class"];
                $block_class = substr($block["class"], 0, strlen($block["class"]) - 3);
                
                // get block gui class
                include_once("./" . self::$locations[$gui_class] . "classes/" .
                    "class." . $gui_class . ".php");
                $block_gui = new $gui_class();
                if (isset($this->block_property[$block["type"]])) {
                    $block_gui->setProperties($this->block_property[$block["type"]]);
                }
                $block_gui->setRepositoryMode($this->getRepositoryMode());
                $block_gui->setEnableEdit($this->getEnableEdit());
                $block_gui->setAdminCommands($this->getAdminCommands());
                
                // get block for custom blocks
                if ($block["custom"]) {
                    $path = "./" . self::$locations[$gui_class] . "classes/" .
                        "class." . $block_class . ".php";
                    if (file_exists($path)) {
                        include_once($path);
                        $app_block = new $block_class($block["id"]);
                    } else {
                        // we only need generic block
                        $app_block = new ilCustomBlock($block["id"]);
                    }
                    $block_gui->setBlock($app_block);
                    if (isset($block["ref_id"])) {
                        $block_gui->setRefId($block["ref_id"]);
                    }
                }
    
                $ilCtrl->setParameter($this, "block_type", $block_gui->getBlockType());
                $this->tpl->setCurrentBlock("col_block");
                
                $html = $ilCtrl->getHTML($block_gui);

                // don't render a block if it's empty
                if ($html != "") {
                    $this->tpl->setVariable("BLOCK", $html);
                    $this->tpl->parseCurrentBlock();
                    $ilCtrl->setParameter($this, "block_type", "");
                }
                
                // count (moveable) blocks
                if ($block["type"] != "pdfeedb" &&
                    $block["type"] != "news") {
                    $i++;
                } else {
                    $sum_moveable--;
                }
            }
        }
    }
    
    /**
    * Add hidden block and create block selectors.
    */
    public function addHiddenBlockSelector()
    {
        /**
         * @var $lng ilLanguage
         * @var $ilUser ilObjUser
         * @var $ilCtrl ilCtrl
         * $var $tpl ilTemplate
         */
        $lng = $this->lng;
        $ilUser = $this->user;
        $ilCtrl = $this->ctrl;

        // show selector for hidden blocks
        include_once("Services/Block/classes/class.ilBlockSetting.php");
        $hidden_blocks = array();

        foreach ($this->blocks[$this->getSide()] as $block) {
            include_once("./" . self::$locations[$block["class"]] . "classes/" .
                "class." . $block["class"] . ".php");
                
            if ($block["custom"] == false) {
                if ($ilCtrl->getContextObjType() == "user") {	// personal desktop
                    if (ilBlockSetting::_lookupDetailLevel($block["type"], $ilUser->getId()) == 0) {
                        $hidden_blocks[$block["type"]] = $lng->txt('block_show_' . $block["type"]);
                    }
                } elseif ($ilCtrl->getContextObjType() != "") {
                    if (ilBlockSetting::_lookupDetailLevel(
                        $block["type"],
                        $ilUser->getId(),
                        $ilCtrl->getContextObjId()
                    ) == 0) {
                        $hidden_blocks[$block["type"] . "_" . $ilCtrl->getContextObjId()] = $lng->txt('block_show_' . $block["type"]);
                    }
                }
            } else {
                if (ilBlockSetting::_lookupDetailLevel(
                    $block["type"],
                    $ilUser->getId(),
                    $block["id"]
                ) == 0) {
                    include_once("./Services/Block/classes/class.ilCustomBlock.php");
                    $cblock = new ilCustomBlock($block["id"]);
                    $hidden_blocks[$block["type"] . "_" . $block["id"]] = sprintf($lng->txt('block_show_x'), $cblock->getTitle());
                }
            }
        }
        if (count($hidden_blocks) > 0) {
            foreach ($hidden_blocks as $id => $title) {
                $ilCtrl->setParameter($this, 'block', $id);
                $this->action_menu->addItem($title, '', $ilCtrl->getLinkTarget($this, 'activateBlock'));
                $ilCtrl->setParameter($this, 'block', '');
            }
        }
        
        // create block selection list
        if (!$this->getRepositoryMode() || $this->getEnableEdit()) {
            $add_blocks = array();
            if ($this->getSide() == IL_COL_RIGHT) {
                if (is_array($this->custom_blocks[$this->getColType()])) {
                    foreach ($this->custom_blocks[$this->getColType()] as $block_class) {
                        include_once("./" . self::$locations[$block_class] . "classes/" .
                            "class." . $block_class . ".php");
                        $block_gui = new $block_class();
                        $block_type = $block_gui->getBlockType();

                        // check if block type is globally (de-)activated
                        if ($this->isGloballyActivated($block_type)) {
                            // check if number of blocks is limited
                            if (!$this->exceededLimit($block_type)) {
                                $add_blocks[$block_type] = $lng->txt('block_create_' . $block_type);
                            }
                        }
                    }
                }
            }
            if (count($add_blocks) > 0) {
                foreach ($add_blocks as $id => $title) {
                    $ilCtrl->setParameter($this, 'block_type', $id);
                    $this->action_menu->addItem($title, '', $ilCtrl->getLinkTarget($this, 'addBlock'));
                    $ilCtrl->setParameter($this, 'block_type', '');
                }
            }
        }
    }


    /**
    * Update Block (asynchronous)
    */
    public function updateBlock()
    {
        $ilCtrl = $this->ctrl;
        
        $this->determineBlocks();
        $i = 1;
        $sum_moveable = count($this->blocks[$this->getSide()]);

        foreach ($this->blocks[$this->getSide()] as $block) {
            include_once("./" . self::$locations[$block["class"]] . "classes/" .
                "class." . $block["class"] . ".php");
                
            // set block id to context obj id,
            // if block is not a custom block and context is not personal desktop
            if (!$block["custom"] && $ilCtrl->getContextObjType() != "" && $ilCtrl->getContextObjType() != "user") {
                $block["id"] = $ilCtrl->getContextObjId();
            }
                
            //if (is_int(strpos($_GET["block_id"], "block_".$block["type"]."_".$block["id"])))

            if ($_GET["block_id"] == "block_" . $block["type"] . "_" . $block["id"]) {
                $gui_class = $block["class"];
                $block_class = substr($block["class"], 0, strlen($block["class"]) - 3);
                
                $block_gui = new $gui_class();
                $block_gui->setProperties($this->block_property[$block["type"]]);
                $block_gui->setRepositoryMode($this->getRepositoryMode());
                $block_gui->setEnableEdit($this->getEnableEdit());
                $block_gui->setAdminCommands($this->getAdminCommands());
                
                // get block for custom blocks
                if ($block["custom"]) {
                    include_once("./" . self::$locations[$gui_class] . "classes/" .
                        "class." . $block_class . ".php");
                    $app_block = new $block_class($block["id"]);
                    $block_gui->setBlock($app_block);
                    $block_gui->setRefId($block["ref_id"]);
                }

                $ilCtrl->setParameter($this, "block_type", $block["type"]);
                echo $ilCtrl->getHTML($block_gui);
                exit;
            }
            
            // count (moveable) blocks
            if ($block["type"] != "pdfeedb"
                && $block["type"] != "news") {
                $i++;
            } else {
                $sum_moveable--;
            }
        }
        echo "Error: ilColumnGUI::updateBlock: Block '" .
            $_GET["block_id"] . "' unknown.";
        exit;
    }

    /**
    * Activate hidden block
    */
    public function activateBlock()
    {
        $ilUser = $this->user;
        $ilCtrl = $this->ctrl;

        if ($_GET["block"] != "") {
            $block = explode("_", $_GET["block"]);
            include_once("Services/Block/classes/class.ilBlockSetting.php");
            ilBlockSetting::_writeDetailLevel($block[0], 2, $ilUser->getId(), $block[1]);
        }

        $ilCtrl->returnToParent($this);
    }

    /**
    * Add a block
    */
    public function addBlock()
    {
        $ilCtrl = $this->ctrl;
        
        $class = array_search($_GET["block_type"], self::$block_types);

        $ilCtrl->setCmdClass($class);
        $ilCtrl->setCmd("create");
        include_once("./" . self::$locations[$class] . "classes/class." . $class . ".php");
        $block_gui = new $class();
        $block_gui->setProperties($this->block_property[$_GET["block_type"]]);
        $block_gui->setRepositoryMode($this->getRepositoryMode());
        $block_gui->setEnableEdit($this->getEnableEdit());
        $block_gui->setAdminCommands($this->getAdminCommands());
        
        $ilCtrl->setParameter($this, "block_type", $_GET["block_type"]);
        $html = $ilCtrl->forwardCommand($block_gui);
        $ilCtrl->setParameter($this, "block_type", "");
        return $html;
    }
    
    /**
    * Determine which blocks to show.
    */
    public function determineBlocks()
    {
        $ilUser = $this->user;
        $ilCtrl = $this->ctrl;
        $ilSetting = $this->settings;

        include_once("./Services/Block/classes/class.ilBlockSetting.php");
        $this->blocks[IL_COL_LEFT] = array();
        $this->blocks[IL_COL_RIGHT] = array();
        $this->blocks[IL_COL_CENTER] = array();
        
        $user_id = ($this->getColType() == "pd")
            ? $ilUser->getId()
            : 0;

        $def_nr = 1000;
        if (is_array($this->default_blocks[$this->getColType()])) {
            foreach ($this->default_blocks[$this->getColType()] as $class => $def_side) {
                $type = self::$block_types[$class];

                if ($this->isGloballyActivated($type)) {
                    $nr = $def_nr++;
                    
                    // extra handling for system messages, feedback block and news
                    if ($type == "news") {		// always show news first
                        $nr = -15;
                    }
                    if ($type == "cal") {
                        $nr = -8;
                    }
                    if ($type == "pdfeedb") {		// always show feedback request second
                        $nr = -10;
                    }
                    if ($type == "clsfct") {		// mkunkel wants to have this on top
                        $nr = -16;
                    }
                    $side = ilBlockSetting::_lookupSide($type, $user_id);
                    if ($side === false) {
                        $side = $def_side;
                    }
                    if ($side == IL_COL_LEFT) {
                        $side = IL_COL_RIGHT;
                    }
                    
                    $this->blocks[$side][] = array(
                        "nr" => $nr,
                        "class" => $class,
                        "type" => $type,
                        "id" => 0,
                        "custom" => false);
                }
            }
        }
        
        if (!$this->getRepositoryMode()) {
            include_once("./Services/Block/classes/class.ilCustomBlock.php");
            $custom_block = new ilCustomBlock();
            $custom_block->setContextObjId($ilCtrl->getContextObjId());
            $custom_block->setContextObjType($ilCtrl->getContextObjType());
            $c_blocks = $custom_block->queryBlocksForContext();
    
            foreach ($c_blocks as $c_block) {
                $type = $c_block["type"];
                
                if ($this->isGloballyActivated($type)) {
                    $class = array_search($type, self::$block_types);
                    $nr = $def_nr++;
                    $side = ilBlockSetting::_lookupSide($type, $user_id, $c_block["id"]);
                    if ($side === false) {
                        $side = IL_COL_RIGHT;
                    }
    
                    $this->blocks[$side][] = array(
                        "nr" => $nr,
                        "class" => $class,
                        "type" => $type,
                        "id" => $c_block["id"],
                        "custom" => true);
                }
            }
        } else {	// get all subitems
            include_once("./Services/Block/classes/class.ilCustomBlock.php");
            $rep_items = $this->getRepositoryItems();

            foreach ($this->rep_block_types as $block_type) {
                if ($this->isGloballyActivated($block_type)) {
                    if (!is_array($rep_items[$block_type])) {
                        continue;
                    }
                    foreach ($rep_items[$block_type] as $item) {
                        $costum_block = new ilCustomBlock();
                        $costum_block->setContextObjId($item["obj_id"]);
                        $costum_block->setContextObjType($block_type);
                        $c_blocks = $costum_block->queryBlocksForContext();
                        $c_block = $c_blocks[0];
                        
                        $type = $block_type;
                        $class = array_search($type, self::$block_types);
                        $nr = $def_nr++;
                        $side = ilBlockSetting::_lookupSide($type, $user_id, $c_block["id"]);
                        if ($side === false) {
                            $side = IL_COL_RIGHT;
                        }
            
                        $this->blocks[$side][] = array(
                            "nr" => $nr,
                            "class" => $class,
                            "type" => $type,
                            "id" => $c_block["id"],
                            "custom" => true,
                            "ref_id" => $item["ref_id"]);
                    }
                }
            }
                                        
            // repository object custom blocks
            include_once("./Services/Block/classes/class.ilCustomBlock.php");
            $custom_block = new ilCustomBlock();
            $custom_block->setContextObjId($ilCtrl->getContextObjId());
            $custom_block->setContextObjType($ilCtrl->getContextObjType());
            $c_blocks = $custom_block->queryBlocksForContext(false); // get all sub-object types
            
            foreach ($c_blocks as $c_block) {
                $type = $c_block["type"];
                $class = array_search($type, self::$block_types);
                
                if ($class) {
                    $nr = $def_nr++;
                    $side = IL_COL_RIGHT;
                        
                    $this->blocks[$side][] = array(
                        "nr" => $nr,
                        "class" => $class,
                        "type" => $type,
                        "id" => $c_block["id"],
                        "custom" => true);
                }
            }
        }
        
        
        $this->blocks[IL_COL_LEFT] =
            ilUtil::sortArray($this->blocks[IL_COL_LEFT], "nr", "asc", true);
        $this->blocks[IL_COL_RIGHT] =
            ilUtil::sortArray($this->blocks[IL_COL_RIGHT], "nr", "asc", true);
        $this->blocks[IL_COL_CENTER] =
            ilUtil::sortArray($this->blocks[IL_COL_CENTER], "nr", "asc", true);
    }

    /**
    * Check whether a block type is globally activated
    */
    protected function isGloballyActivated($a_type)
    {
        $ilSetting = $this->settings;
        $ilCtrl = $this->ctrl;

        if ($a_type == 'pdfeed') {
            return false;
        }

        if (isset($this->check_global_activation[$a_type]) && $this->check_global_activation[$a_type]) {
            if ($a_type == 'pdnews') {
                return ($this->dash_side_panel_settings->isEnabled($this->dash_side_panel_settings::NEWS) &&
                    $ilSetting->get('block_activated_news'));
            } elseif ($a_type == 'pdmail') {
                return $this->dash_side_panel_settings->isEnabled($this->dash_side_panel_settings::MAIL);
            } elseif ($a_type == 'pdtasks') {
                return $this->dash_side_panel_settings->isEnabled($this->dash_side_panel_settings::TASKS);
            } elseif ($a_type == 'news') {
                include_once 'Services/Container/classes/class.ilContainer.php';
                return
                    $ilSetting->get('block_activated_news') &&

                    (!in_array($ilCtrl->getContextObjType(), ["grp", "crs"]) ||
                        ilContainer::_lookupContainerSetting(
                            $GLOBALS['ilCtrl']->getContextObjId(),
                            ilObjectServiceSettingsGUI::USE_NEWS,
                            true
                    )) &&
                    ilContainer::_lookupContainerSetting(
                        $GLOBALS['ilCtrl']->getContextObjId(),
                        'cont_show_news',
                        true
                    );
            } elseif ($ilSetting->get("block_activated_" . $a_type)) {
                return true;
            } elseif ($a_type == 'cal') {
                include_once('./Services/Calendar/classes/class.ilCalendarSettings.php');
                return ilCalendarSettings::lookupCalendarActivated($GLOBALS['ilCtrl']->getContextObjId());
            } elseif ($a_type == 'pdcal') {
                if (!$this->dash_side_panel_settings->isEnabled($this->dash_side_panel_settings::CALENDAR)) {
                    return false;
                }
                include_once('./Services/Calendar/classes/class.ilCalendarSettings.php');
                return ilCalendarSettings::_getInstance()->isEnabled();
            } elseif ($a_type == "tagcld") {
                $tags_active = new ilSetting("tags");
                return (bool) $tags_active->get("enable", false);
            } elseif ($a_type == "clsfct") {
                if ($ilCtrl->getContextObjType() == "cat") {	// taxonomy presentation in classification block
                    return true;
                }
                $tags_active = new ilSetting("tags");		// tags presentation in classification block
                return (bool) $tags_active->get("enable", false);
            }
            return false;
        }
        return true;
    }

    /**
    * Check whether limit is not exceeded
    */
    protected function exceededLimit($a_type)
    {
        $ilSetting = $this->settings;
        $ilCtrl = $this->ctrl;

        if ($this->check_nr_limit[$a_type]) {
            if (!$this->getRepositoryMode()) {
                include_once("./Services/Block/classes/class.ilCustomBlock.php");
                $costum_block = new ilCustomBlock();
                $costum_block->setContextObjId($ilCtrl->getContextObjId());
                $costum_block->setContextObjType($ilCtrl->getContextObjType());
                $costum_block->setType($a_type);
                $res = $costum_block->queryCntBlockForContext();
                $cnt = (int) $res[0]["cnt"];
            } else {
                return false;		// not implemented for repository yet
            }
            
            
            if ($ilSetting->get("block_limit_" . $a_type) > $cnt) {
                return false;
            } else {
                return true;
            }
        }
        return false;
    }

    /**
     * Stores the block sequence asynchronously
     */
    public function saveBlockSortingAsynch()
    {
        /**
         * @var $ilUser ilObjUser
         */
        $ilUser = $this->user;

        $response = new stdClass();
        $response->success = false;

        if (!isset($_POST[IL_COL_LEFT]['sequence']) && !isset($_POST[IL_COL_RIGHT]['sequence'])) {
            echo json_encode($response);
            return;
        };

        if (in_array($this->getColType(), array('pd'))) {
            $response->success = true;
            
            foreach (array(IL_COL_LEFT => (array) $_POST[IL_COL_LEFT]['sequence'], IL_COL_RIGHT => (array) $_POST[IL_COL_RIGHT]['sequence']) as $side => $blocks) {
                $i = 2;
                foreach ($blocks as  $block) {
                    $bid = explode('_', $block);
                    ilBlockSetting::_writeNumber($bid[1], $i, $ilUser->getId(), $bid[2]);
                    ilBlockSetting::_writeSide($bid[1], $side, $ilUser->getId(), $bid[2]);

                    $i += 2;
                }
            }
        }

        echo json_encode($response);
        exit();
    }

    /**
     * @param \ilAdvancedSelectionListGUI $action_menu
     * @return ilColumnGUI
     */
    public function setActionMenu($action_menu)
    {
        $this->action_menu = $action_menu;
        return $this;
    }

    /**
     * @return \ilAdvancedSelectionListGUI
     */
    public function getActionMenu()
    {
        return $this->action_menu;
    }
}
