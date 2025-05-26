import $ from 'jquery';
import 'datatables.net-bs5';
import 'datatables.net-bs5/css/dataTables.bootstrap5.min.css';

$(document).ready(function () {
    $('#article-table').DataTable({
        ajax: '/article/ajax/list',
        columns: [
            { data: 'id' },
            { data: 'titre' },
            { data: 'contenu' },
            { data: 'auteur' },
            { data: 'date' },
            {
                data: 'actions',
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return data;  // ici on affiche le HTML généré par Twig côté serveur
                }
            }
        ]
    });
});
$(document).on('click', '.btn-like', function() {
    const btn = $(this);
    const articleId = btn.data('article-id');

    $.post(`/article/${articleId}/like`)
        .done(function(data) {
            if (data.liked) {
                btn.removeClass('btn-outline-danger').addClass('btn-danger');
                btn.attr('aria-pressed', 'true');
                btn.attr('title', 'Retirer le like');
            } else {
                btn.removeClass('btn-danger').addClass('btn-outline-danger');
                btn.attr('aria-pressed', 'false');
                btn.attr('title', 'Ajouter un like');
            }
            btn.find('.likes-count').text(data.likesCount);
        })
        .fail(function() {
            alert('Erreur lors du like.');
        });
});


