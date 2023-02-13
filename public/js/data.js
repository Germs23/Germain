$(function(){
    $('#tableArticle').DataTable({
        "ajax": {
            url: "/getAllArticles",
            dataSrc: ''
        },
        columns: [
            {data: "title"},
            {data: "category"},
            {data: "image",
                render: function(data) {
                if (!data){
                    return 'pas d\'image importé';
                } else if(data === 'http://placehold.it/350x150') {
                    return `<a href="/images/products/no-image.jpg">Image par défaut</a>`;
                } else {
                    return `<a href="/images/products/${data}">Image de l'article</a>`;
                }
            }
            }]
    });
});

function hide_dataTable(id1,id2)
{
    if (document.getElementById(id2).style.display === 'none'){
        document.getElementById(id1).style.display = 'none';
        document.getElementById(id2).style.display = 'block';
    } else {
        document.getElementById(id1).style.display = 'block';
        document.getElementById(id2).style.display = 'none';
    }
}
