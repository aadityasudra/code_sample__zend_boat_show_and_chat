//===========================================================================
// Function:       print_r()
// Purpose:        return structure of an object
// Example:        alert(print_r(OObj, true, '\t'));
// IMPORTANT NOTE: When using the recursive flag of this function
//                 you *have* to be careful as JS likes to loop back around
//                 between objects infinitly.  a future 'seen' flag will
//                 (hopefully) get built in.
// History:        2005.12.02 - JQuattlebaum - Initial Revision
//===========================================================================

function print_r(OObj, recurse, prependingSpace) {
  if(typeof OObj == 'object') {
    var treeDisplay = '';
    for(var key in OObj) {
      treeDisplay += prependingSpace+'['+key+'] => \''+OObj[key]+'\' ('+typeof OObj[key]+')\n';
      if(recurse && typeof OObj[key] == 'object') {
        treeDisplay += print_r(OObj[key], recurse, prependingSpace+'\t');
      }
    }
    return treeDisplay;
  } else {
    return 'not an object!';
  }
}
