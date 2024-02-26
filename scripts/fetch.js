fetchAllData = () =>{
    $.ajax({
        type: "POST",
        url: "../php/fetch.php",
        data: {
            r : 'fetchAllData',
        },
        dataType: "",
        success: function (response) {
            console.log(response);
        }
    });
}