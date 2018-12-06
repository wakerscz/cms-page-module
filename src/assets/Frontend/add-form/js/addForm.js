/**
 * Copyright (c) 2018 Wakers.cz
 *
 * @author Jiří Zapletal (http://www.wakers.cz, zapletal@wakers.cz)
 *
 */

$(function ()
{
    $.nette.ext(
    {
        load: function ()
        {
            var $form = $('#wakers_page_add_form'),
                $inputUrl = $form.find('input[name="url"]'),
                $inputName = $form.find('input[name="name"]'),
                $inputParentPage = $form.find('select[name="parentPageId"]'),
                $inputUrlType = $form.find('select[name="urlType"]');


            var activeLang = $form.data('active-lang'),
                languages = $form.data('languages'),
                pages = $form.data('pages');


            var name = '',
                parentPageId = 0;


            $inputName.on('change keyup', function ()
            {
                name = Nette.webalize($(this).val());
                parentPageId = parseInt($inputParentPage.val());

                $.setPageUlr();
            });


            $inputParentPage.on('change', function ()
            {
                parentPageId = parseInt($(this).val());
                $.setPageUlr();
            });


            $inputUrlType.on('change', function ()
            {
                var value = parseInt($(this).val());

                if (value === 0)
                {
                    $inputUrl.attr('readonly', 'readonly');
                }
                else
                {
                    $inputUrl.removeAttr('readonly');
                }

                $.setPageUlr();
            });


            $.setPageUlr = function ()
            {
                var url = '';

                if ($inputUrl.is('[readonly]') === true)
                {
                    var parentUrl = (parentPageId === 0) ? '' : pages[parentPageId] + '/';

                    url = (languages.length > 1 &&  parentPageId === 0) ? activeLang + '/' + name : parentUrl + name;
                }
                else
                {
                    url = (languages.length > 1) ? activeLang + '/' + name : name;
                }

                $inputUrl.val(url);
            }
        }
    });
});