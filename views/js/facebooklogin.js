/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$(document).ready(function(){
    $("#submitCreateFacebook").click(function(){
        checkRegisterState();
    });
    $("#submitLoginFacebook").click(function(){
        checkLoginState();
    });
});

function submitFacebookRegister() {
    FB.api('/me?fields=first_name,last_name,email,id', function(response) {
        var firsName = response.first_name;
        var lastName = response.last_name;
        var email = response.email;
        var id = response.id;
        
        var call = $.ajax({
            type: 'POST',
            url: baseUri + '?fc=module&module=facebooklogin&controller=login',
            async: true,
            cache: false,
            dataType : "json",
            headers: { "cache-control": "no-cache" },
            data:
            {
                submitCreateFacebook: 1,
                ajax: true,
                firstName: firsName,
                lastName: lastName,
                email: email,
                idFacebook: id,
                token: token
            }
        });
        
        call.success(function(response){           
            window.location = baseUri;
        });
        
        call.error(function(){
            showErrorMessage("Ocurrio un error al intentar crear el usuario en el sistema"); 
        });
        
    });    
}

function submitFacebookLogin(){
    FB.api('/me?fields=id', function(response) {
        var idFacebook = response.id;
        var call = $.ajax({
            type: 'POST',
            url: baseUri + '?fc=module&module=facebooklogin&controller=login',
            async: true,
            cache: false,
            dataType: 'json',
            headers: { 'cache-control': 'no-cache' },
            data:{
                submitLoginFacebook: 1,
                ajax: true,
                idFacebook: idFacebook,
                token: token
            }
        });
       
        call.success(function(response){
            window.location = baseUri;
        });
       
        call.error(function(){
            showErrorMessage("El usuario no se encuentra registrado en el sistema");
        });       
    });
}

function statusChangeCallback(response) {
    if (response.status === 'connected') {
        submitFacebookRegister();   
    }
}

function userLogin(response){
    if (response.status === 'connected') {
        submitFacebookLogin();
    }
}

function checkLoginState() {
    FB.login(function(response){
        userLogin(response);
    }, {scope: 'public_profile, email'});
}

function checkRegisterState() {
    FB.login(function(response){
        statusChangeCallback(response);
    }, {scope: 'public_profile, email'});
}

function showErrorMessage(error){
    if (!!$.prototype.fancybox)
    {
        $.fancybox.open([
        {
            type: 'inline',
            autoScale: true,
            minHeight: 30,
            content: "<p class='fancybox-error'>" + error + '</p>'
        }],
        {
            padding: 0
        });
    }else{
        alert(error);
    }    
}

// Load the SDK asynchronously
(function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
    js = d.createElement(s); js.id = id;
    js.src = "//connect.facebook.net/en_US/sdk.js";
    fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));
 
window.fbAsyncInit = function() {
    FB.init({
      appId: $("#appId").val(),
      cookie: true,  // enable cookies to allow the server to access the session
      xfbml: true,  // parse social plugins on this page
      version: 'v2.5' // use graph api version 2.5
    });

    FB.getLoginStatus(function(response) {
        userLogin(response);
    });
}
