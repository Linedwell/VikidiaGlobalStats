<?php
/**
 * vikidiaGlobalStats SpecialPage for VikidiaGlobalStats extension
 *
 * @file
 * @ingroup Extensions
 */
class SpecialVikidiaGlobalStats extends SpecialPage {
	public function __construct() {
		parent::__construct( 'vikidiaGlobalStats' );
	}

	/**
	 * Show the page to the user
	 *
	 * @param string $sub The subpage string argument (if any).
	 */
	public function execute( $sub ) {
		$out = $this->getOutput();
		$out->setPageTitle( $this->msg( 'special-vikidiaGlobalStats-title' ) );
		//$out->addHelpLink( 'How to become a MediaWiki hacker' );
		$out->addWikiMsg( 'special-vikidiaGlobalStats-intro' );

    $target = 
        trim(
				    str_replace( '_', ' ',
					  $this->getRequest()->getText( 'target', $sub ) ) );
                                        
    $this->showSearchForm($target);
    
    $stats = $this->generateStats($target); 
    //echo print_r($stats);
    if($target)
        $this->showStats($target, $stats);
    
  }
  
  function showSearchForm($target) {
    global $wgScript;
    
    $lang = $this->getLanguage();
    
    $title = htmlspecialchars( $this->getPageTitle()->getPrefixedText(), ENT_QUOTES );
    $action = htmlspecialchars( $wgScript, ENT_QUOTES );
    $username = htmlspecialchars( $this->msg( 'vikidiaglobalstats-username' )->text() );
    $search = htmlspecialchars( $this->msg( 'vikidiaglobalstats-search' )->text() );
    
    $out = $this->getOutput();
    $output = "<fieldset>".
      Html::element( 'legend', [], $this->msg( 'vikidiaglobalstats-searchuser' )->text() ).
      "<form method='get' action='$action'>\n".
      "<input type='hidden' name='title' value='{$title}' />\n".
      "<table border='0'>\n".
      "<tr>\n".
      "<td align='right'>$username</td>\n".
      "<td align='left'><input type='text' size='50' name='target' value='".htmlspecialchars( $target )."'/>\n".
      "<td colspan='2' align='center'><input type='submit' name='submit' value='$search' /></td>\n";
      
      $output .= Html::closeElement( 'tr' );
      $output .= Html::closeElement( 'table' );
      $output .= Html::closeElement( 'form' );
     
      
      
    $output .= Html::closeElement( 'fieldset' );
    $out->addHTML($output);
    
  }
 
	function showStats($target, $stats) {
 
    $lang = $this->getLanguage();
 
    $out = $this->getOutput();
    $output = "<fieldset>".
      Html::element( 'legend', [], $this->msg( 'vikidiaglobalstats-globalstats' )->text() );
    $output .= "<table class=\"wikitable contributionscores plainlinks sortable\" >\n" .
			"<tr class='header'>\n" .
			Html::element( 'th', [], $this->msg( 'vikidiaglobalstats-project' )->text() ) .
      Html::element( 'th', [], $this->msg( 'vikidiaglobalstats-registered' )->text() ) .
			Html::element( 'th', [], $this->msg( 'vikidiaglobalstats-blocks' )->text() ) .
			Html::element( 'th', [], $this->msg( 'vikidiaglobalstats-editcount' )->text() ) .
			Html::element( 'th', [], $this->msg( 'vikidiaglobalstats-groups' )->text() );
   
    $altrow = '';   
   
    while($s = current($stats)) {
      try {
        $xml = new SimpleXMLElement($s);
      } catch(Exception $e) {
        return false;
      }
      $wikiname = key($stats).".vikidia.org";
      $user = $xml->{'query'}[0]->{'users'}[0]->{'user'}[0];
      if($user && ! $user['missing']) {
        $userlink = "https://".$wikiname."/wiki/User:".$user['name'];

        $userregistration = $lang->timeanddate($user['registration']); 

        $blklog = "https://".$wikiname."/wiki/Special:Log/block?page=".$user['name'];
        $blkexpiry = "&mdash;";
        $blkreason = "";
        if($user['blockid']) {
          $blkreason = $user['blockreason'];
          $blkexpiry = $this->msg( 'vikidiaglobalstats-blocked-indef' )->text().".";
          if($user['blockexpiry'] != "infinity")
            $blkexpiry = $this->msg( 'vikidiaglobalstats-blocked' )->text(). " ".$lang->timeanddate($user['blockexpiry']).".";
        }
        
        $contribslink = "https://".$wikiname."/wiki/Special:Contributions/".$user['name'];
        $edits = $user['editcount'];

        $rawgrps = $user->{'groupmemberships'}->children();
        $grps = array();
        foreach($rawgrps as $g) {
          array_push($grps, $g['group']);
        }
        $grps = implode(",",$grps);
    
        $output .= Html::closeElement( 'tr' );
      
        $output .= "<tr class='{$altrow}'>\n" .
          "<td><a href=$userlink>$wikiname</a></td>\n".
          "<td>$userregistration</td>\n".
          "<td><a href=$blklog>$blkexpiry</a> ".$blkreason."</td>\n".
          "<td><a href=$contribslink>$edits</a></td>\n".
          "<td>$grps</td>\n";
        
        if($altrow == ''){
				  $altrow = 'odd ';
			  } else {
				  $altrow = '';
			  }
      }
      
      next($stats);
    }          
    $output .= Html::closeElement( 'tr' );
    $output .= Html::closeElement( 'table' );
    $output .= Html::closeElement( 'fieldset' );
    $out->addHTML($output);
 
  }
  
  function generateStats($target) {
    $stats = array();
    $projects = array('ca','de','el','en','es','eu','fr','hy','it','ru','scn');
    foreach($projects as $prj) {
        $wikiname = $prj.".vikidia.org";
        $url = "https://".$wikiname."/w/api.php?action=query&list=users&ususers=".$target."&usprop=blockinfo|groupmemberships|editcount|registration&format=xml";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $stats[$prj] = curl_exec($ch);
        curl_close($ch);
    }
    return $stats;
  }
  

	protected function getGroupName() {
		return 'other';
	}
}
