<?php
/* $Id$ 
 * Allows the setting of categories by each participant
 * of an event
 *
 * Multiple categories can be added by each participant and 
 * stored seperately for that user. Global categories will be visible
 * by all participants, but can only be added/removed by the owner
 * in the edit-entry form.
 *
 */
include_once 'includes/init.php';
load_user_categories();

$error = '';

if ( empty ( $id ) )
  $error = translate( 'Invalid entry id' ) . '.';
else if ( $CATEGORIES_ENABLED != 'Y' )
  $error = print_not_auth () . '.';
else if ( empty ( $categories ) )
  $error = translate( 'You have not added any categories' ) . '.';

// make sure user is a participant
$res = dbi_execute ( 'SELECT  cal_status FROM webcal_entry_user ' .
  'WHERE cal_id = ? AND cal_login = ?', array ( $id, $login ) );
if ( $res ) {
  if ( $row = dbi_fetch_row ( $res ) ) {
    if ( $row[0] == 'D' ) // User deleted themself
      $error = print_not_auth () . '.';
  } else {
    // not a participant for this event
    $error = print_not_auth () . '.';
  }
  dbi_free_result ( $res );
} else {
  $error = db_error ();
}
 
$cat_id = getPostValue ( 'cat_id' );
$cat_ids = array();
$cat_name = array();
$catNames = '';

//get user's categories for this event
$globals_found = false;
$categories = get_categories_by_id ( $id, $login, true );
if ( ! empty ( $categories ) ) {
  $catNames = implode(', ', $categories );
  $keys = array_keys ( $categories );
  $catList = implode(',', $keys );
  sort ( $keys );
  if ( $keys[0] < 0 )
    $globals_found = true;    
}

// Get event name and make sure event exists
$event_name = '';
$res = dbi_execute ( 'SELECT cal_name FROM webcal_entry ' .
  'WHERE cal_id = ?', array ( $id ) );
if ( $res ) {
  if ( $row = dbi_fetch_row ( $res ) ) {
    $event_name = $row[0];
  } else {
    // No such event
    $error = translate( 'Invalid entry id' ) . '.';
  }
  dbi_free_result ( $res );
} else {
  $error = db_error ();
}

// If this is the form handler, then save now
if ( ! empty ( $cat_id ) && empty ( $error ) ) {
 dbi_execute ( 'DELETE FROM webcal_entry_categories WHERE cal_id = ? ' .
    'AND ( cat_owner = ? )', array ( $id, $login ) );
 $categories = explode (',', $cat_id );

 $sql_params = array();
 for ( $i =0, $cnt = count( $categories ); $i < $cnt; $i++ ) {
   //don't process Global Categories
   if ( $categories[$i] > 0 ) {
   $names = array();
   $values = array();
   $names[] = 'cal_id';
   $sql_params[]  = $id; 
   $values[]  = '?'; 
   $names[] = 'cat_id';
   $sql_params[]  = abs($categories[$i]);
   $values[]  = '?'; 
   $names[] = 'cat_order';
   $sql_params[]  = ($i +1);
   $values[]  = '?'; 
   $names[] = 'cat_owner';
   $sql_params[]  = $login; 
   $values[]  = '?'; 
   $sql = 'INSERT INTO webcal_entry_categories ( ' . implode ( ', ', $names ) .
     ' ) VALUES ( ' . implode ( ', ', $values ) . ' )';
   } 
 }
 $view_type = 'view_entry';  
  
 if ( ! dbi_execute ( $sql, $sql_params ) ) {
    $error = db_error ();
  } else {
    $url = $view_type .".php?id=$id";
    if ( ! empty ( $date ) )
      $url .= "&amp;date=$date";
    do_redirect ( $url );
  }
}
//set up variables for inclusion later
$setCatStr = translate( 'Set Category' );
$briefStr = translate( 'Brief Description' );
$catHelpStr = tooltip( 'category-help' );
$catStr = translate( 'Category' );
$editStr = translate( 'Edit' );
$globalNoteStr = ( $globals_found ? translate( 'Global Categories can not be changed'):'');
$saveStr = translate( 'Save' );

$INC = array('js/set_entry_cat.php/true');
print_header($INC);

if ( ! empty ( $error ) ) { 
  echo print_error ( $error );
} else {
echo <<<EOT
<h2>{$setCatStr}</h2>
<form action="set_entry_cat.php" method="post" name="selectcategory">
  <input type="hidden" name="date" value="{$date}" />
  <input type="hidden" name="id" value="{$id}" />
  <table border="0" cellpadding="5">
    <tr class="aligntop">
      <td class="bold">{$briefStr}:</td>
      <td>{$event_name}</td>
    </tr>
    <tr>
      <td class="tooltip" title="{$catHelpStr}" valign="top">
        <label for="entry_categories">{$catStr}:<br /></label>
        <input type="button" value="{$editStr}" onclick="editCats(event)" />
       </td><td valign="top">
       <input readonly="readonly" type="text" name="catnames" value="{$catNames}" size="75" onclick="editCats(event)"/>
       <br />{$globalNoteStr}
       <input  type="hidden" name="cat_id" id="entry_categories" value="{$catList}" />
      </td>
    </tr>
    <tr class="aligntop">
      <td colspan="2"><br />
        <input type="submit" value="{$saveStr}" />
      </td>
    </tr>
  </table>
</form>
EOT;
}
echo print_trailer(); ?>
