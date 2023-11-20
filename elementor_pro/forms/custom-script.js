// Inside custom-script.js
// 
/*
document.addEventListener('elementorFormSubmitResponse', function(event) {
    var response = event.detail.response;
    if (response.data.redirect) {
        window.location.href = response.data.redirect;
    }
});
*/
console.log('Custom script loaded.');
document.addEventListener('elementorFormSubmitResponse', function(event) {
    console.log('Form Submit Response:', event);

    var response = event.detail.response;
    console.log('Response data:', response.data);

    if (response.data && response.data.redirect) {
        console.log('Redirecting to:', response.data.redirect);
        window.location.href = response.data.redirect;
    } else {
        console.log('No redirection data found.');
    }
});
