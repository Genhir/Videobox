/// <reference path="jquery.d.ts" />

(function($: JQueryStatic) {

    // function to set the value of varoius input elements
    function setVal(el: JQuery, value: any): void {
        switch (el.attr("type")) {
            case "radio":
            case "checkbox":
                el.each(function() {
                    if ($(this).attr('value') == value) {
                        $(this).attr("checked", 1);
                    } else {
                        $(this).removeAttr('checked');
                    }
                });
                break;
            case "text":
            case "hidden":
            default:
                el.val(value);
                break;
        }
    }

    function getVal(form: JQuery, name: string): string {
        var p = form.serializeArray();
        for (var i = 0; i < p.length; i++) if (p[i].name == name) return p[i].value;
        return '';
    }

    $(window).on('load', function(evt: JQueryEventObject) {

        $('input.vb-prop-set').each(function(i: number, el: Element) {

            var
                form: JQuery = $(el).parents('form'),
                key: string = $(el).attr('data-key'),
                prefix: string = $(el).attr('data-prefix'),
                values: any = $.parseJSON($(el).val()),
                id: string = $(el).attr('id'),
                item: JQuery = $('#' + id + '-list').find('*[data-set="' + key + '"]'),
                keyInput: JQuery = $('#' + id + '-key');

            // bind listener for property set field
            keyInput.on('change', function(evt: JQueryEventObject) {
                var name = $(evt.target).attr('name');
                if (name) {
                    // get cleansed key value (dash-separated values)
                    var value = getVal(form, name).replace(/(^\W+)|(\W+$)/g, '').replace(/\W+/g, '-').toLowerCase();

                    // return on invalid value
                    if (!value || value == 'new') return false;

                    // set cleansed value
                    setVal($(evt.target), value);

                    // update key if neccesary
                    if (key != value) {
                        values[value] = $.extend({}, values[key]);
                        values[key] = undefined;
                        key = value;
                        values[key]['property_set'] = key;
                        setVal($(el), JSON.stringify(values));
                        item.attr('data-set', key);
                        item.html(key);
                    }
                }
            });

            // bind remove event on property sets
            $('#' + id + '-list').find('.icon-cancel').on('click', function(evt: JQueryEventObject) {
                var itemKey = $(evt.target).parents('li').attr('data-set');

                // remove set
                values[itemKey] = undefined;
                setVal($(el), JSON.stringify(values));

                // save changes
                window['Joomla'].submitbutton('plugin.apply');
            });

            // bind input listeners
            var fields = [
                'input',
                'select',
                'textarea'
            ];
            var qry = '';
            for(var j = 0; j < fields.length; j++){
                if(qry) qry += ', ';
                qry += fields[j] + '[name^="' + prefix + '"], ';
                qry += fields[j] + '[name*=" ' + prefix + '"]';
            }
            form.find(qry).on('change select click', function(evt: JQueryEventObject) {
                var name = $(evt.target).attr('name');
                if (name) {
                    var value = getVal(form, name);
                    if (!values[key]['property_set']) values[key] = {
                        'property_set': key
                    };
                    values[key][name] = value;
                    setVal($(el), JSON.stringify(values));
                }
            });
        });
    });

})(jQuery);