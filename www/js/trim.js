//===========================================================================
// Functions:     ltrim(), rtrim(), trim()
// Purpose:       provide trim functionality
// History:       2006.5.13 - JQuattlebaum - Initial Revision
//                2006.8.3 - JQuattlebaum - found a better example (noel)
//===========================================================================
  function ltrim(str){
    return str.replace(/^\s+/g,'');
  }
  function rtrim(str){
    return str.replace(/\s+$/g,'');
  }
  function trim(str){
    return ltrim(rtrim(str));
  }
