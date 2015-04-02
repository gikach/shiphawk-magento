document.observe("dom:loaded", function() {
    function insertAfter(referenceNode, newNode) {
        referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
    }

    function updateInput() {
        var shiphawk_shipping_origins = document.getElementById("shiphawk_shipping_origins");

        var url = '/shiphawk/index/origins';

        var parameters = {
            origin_id: shiphawk_shipping_origins.value
        };

        new Ajax.Request(url, {
            method: 'post',
            parameters: parameters,
            onSuccess: function(transport)  {

                responce_html  = JSON.parse(transport.responseText);

                var el = document.createElement("div");

                el.id = "origins_select";

                el.update(responce_html);

                shiphawk_shipping_origins.parentNode.replaceChild(el, shiphawk_shipping_origins);
            },
            onLoading:function(transport)
            {

            }
        });
    }

    updateInput();

    var el = document.createElement("div");

    el.id = "type_product";
    var shiphawk_type_of_product = document.getElementById("shiphawk_type_of_product");

    insertAfter(shiphawk_type_of_product, el);

    var typeloader;
    $('shiphawk_type_of_product').observe('keyup', function(event){
        clearTimeout(typeloader);
        typeloader = setTimeout(function(){ respondToClick(event); }, 750);
    });

    function respondToClick(event) {

        var element = event.element();

        var minlength = 3;

        var url = '/shiphawk/index/search';

        var parameters = {
            search_tag: element.value
        };

        if(element.value.length >= minlength  ) {
            new Ajax.Request(url, {
                method: 'post',
                parameters: parameters,
                onSuccess: function(transport)  {

                    responce_html  = JSON.parse(transport.responseText);

                    if(responce_html.shiphawk_error) {
                        alert(responce_html.shiphawk_error);
                    }else{
                        if(responce_html.responce_html) {
                            $('type_product').update(responce_html.responce_html);
                            $('type_product').show();
                        }
                    }

                },
                onLoading:function(transport)
                {
                }
            });
        }
    }
});
    function setItemid(el) {
        $('shiphawk_type_of_product').value = el.innerHTML;
        $('shiphawk_type_of_product_value').value = el.id;
        $('type_product').hide();
    }