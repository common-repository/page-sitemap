jQuery(document).ready(function($){
    //Get all sitemap-list-item items
    var listItems = jQuery('.cst-sitemap-list-item a');
    var itm;
    
    var linkList = new Array();
    var i = 0;
    
    var cnt = listItems.length;
    
    while ( i < cnt ){
        var itmLink = listItems[i].href;
        if( linkList.includes( itmLink ) ){
            var replinks = jQuery('a[href="'+itmLink+'"]');
            var repCnt = replinks.length;
            var r = 1;
            while (r < repCnt ){
                replinks[r].parentNode.parentNode.hidden = true;
                r++;
            }
            
        } else {
            linkList.push(itmLink);
        }
        i++;
    }
    
});

