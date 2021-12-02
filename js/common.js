

// PAGE

var common = {

    elements: new Map(),
    step: 1,
    interval_id: undefined,
    onload_old_func: undefined,
    init: function() {
        add_event(document, 'DOMContentLoaded', common.begin);
        if(window.onload !== null) common.onload_old_func = window.onload;
        window.onload = () => common.onload();
        
    },
    begin: function() {
        common.save();
        Seting(document.body, 'opacity', common.step / 10, common.elements);

    },
    onload: function() {
        common.interval_id = setInterval(common.animation, 500);
    },
    animation: function() {
        if (common.step < 9) { // <= 10
            Seting(document.body, 'opacity', ++common.step / 10, common.elements);
        } else {
            common.restore();
            clearInterval(common.interval_id);
            if(common.onload_old_func !== null) common.onload_old_func();
        }
    },
    save: function() {
        Seting(document.body, 'save', '', common.elements);
    },
    restore: function() {
        //Seting(document.body, 'restore', '', common.elements); //old
        /**/
        for (const key_val of common.elements.entries()) {
            element = key_val[0];
            if (element.parentNode !== null){
                element.outerHTML = key_val[1].outerHTML;
            }
        }/**/
    },
    onload_old_test_func: function() {
        alert('window.onload old func');
    },

};

function search_edit_node(el) {
    let found;
    if (el.nodeName !== 'DIV' && el.nodeName !== 'IMG')
        found = search_edit_node(el.parentNode);
    else found = el;

    return found;
}

function Seting(el, what, value, elements) {
    
    if (el.length >= 0){
        [].forEach.call(el, function(el_i) {
            Seting(el_i, what, value, elements);
        });
    }else{
        if(el.children && el.children.length) {
            Seting(el.children, what, value, elements);
        } else {
            el_found = search_edit_node(el);
            
            switch (what) {
                case 'opacity':
                    el_found.style.opacity = value;
                    el_found.style.height = value*100+'%';
                    break;
            
                case 'hidden':
                    el_found.hidden = value;
                    break;

                case 'save':
                    if(!elements.has(el_found)){
                        let save_element;
                        save_element = {};
                        save_element.element   = el_found;
                        save_element.outerHTML = el_found.outerHTML;
                        elements.set(el_found, save_element);
                    }
                    break;

                case 'restore':
                    save_element = elements.get(el_found);
                    if( el_found.parentNode !== null ){
                        el_found.outerHTML = save_element.outerHTML;
                    } else {
                        console.debug(el_found);
                        //console.debug(el_found.outerHTML.substr(0,100));
                    }
                    break;

                default:
                    break;
            }
            

        }
    }

}

window.onload = () => common.onload_old_test_func();
common.init();
