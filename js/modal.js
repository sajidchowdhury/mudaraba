function openModal(page_name,get_related_id,section) {

    var related_id = $('#'+get_related_id).val();

    if(related_id == '' ){ alert('can not find data for empty data'); return false;}

    $('#modal-xl').modal('show')
    document.getElementById('#modal-xl')
    $('#modal-xl .modal-title').html(section);
    var modal = $(this);        

       $.ajax({
    type: "GET",
    url: page_name+".php",
    
    data: {
      related_id: related_id,
      section: section
    },
    cache: false,
    success: function(data) {
        console.log(data);
        document.getElementById('dash').innerHTML = data;
            $('.select2').select2()

    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })
    },
    error: function(err) {
        console.log(err);
    }

});
}