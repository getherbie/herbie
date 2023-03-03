$(document).ready(function() {
    // delete
    $('body').on('click', 'a.delete', function() {
        $(this).parent().remove();
        return false;
    });

    // add
    $('body').on('click', 'a.add', function() {
        let count = $('div.template').attr('data-count');
        let template = $('div.template div.pure-control-wrap').clone();

        // replace _index_ with count
        $(template).find('[name*=\'_index_\']').each(function() {
            let name = $(this).attr('name');
            name = name.replace('_index_', count);
            $(this).attr('name', name);
        });
        // replace data-index='_index_'
        $(template).find('[data-index]').each(function() {
            let name = $(this).attr('data-index');
            name = name.replace('_index_', count);
            $(this).attr('data-index', name);
        });

        if($(this).hasClass('first')) {
            $(this).after( template );
        } else {
            $(this).parent().after( template );
        }
        $('div.template').attr('data-count', ++count);
        return false;
    });

    // add-multitext
    $('body').on('click', 'a.add-multitext', function() {
        let template = $('div.template-fields div.multitext-input').clone();
        let index = $(this).parent().parent().attr('data-index');
        let key = $(this).parent().parent().attr('data-key');
        let name = $(template).find('input').attr('name');
        name = name.replace('_index_', index);
        name = name.replace('_key_', key);
        $(template).find('input').attr('name', name);
        $(this).closest('.multitext').children(':last-child').after( $(template) );
        return false;
    });

    $('body').on('click', 'a.remove-multitext', function() {
        $(this).parent().remove();
        return false;
    });

    // add-matrix
    $('body').on('click', 'a.add-matrix', function() {
        let parent = $(this).siblings('.matrix-rows');
        let count = $(parent).attr('data-count');
        let index = $(parent).attr('data-index');
        let template = $('div.template div.matrix-rows').clone().html();
        template = template.replace(/__index__/g, count);
        template = template.replace(/_index_/g, index);
        let row = $(parent).children('.matrix-row:last-child');
        if(row.length) {
            row.after( template );
        } else {
            $(parent).html( template );
        }
        $(parent).attr('data-count', ++count);
        return false;
    });

    $('body').on('click', 'a.remove-matrix', function() {
        $(this).parent().remove();
        return false;
    });
});
