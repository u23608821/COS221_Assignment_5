function clickLogin() {

    var emailInput = document.getElementById("username");
    var passwordInput = document.getElementById("password");

    var data = {
        email: emailInput.value,
        password: passwordInput.value
    };

    data.type = "Login";

    // Create a new XMLHttpRequest object
    var xhr = new XMLHttpRequest();

    // Configure the request
    xhr.open('POST', 'https://wheatley.cs.up.ac.za/u24634434/COS221/api.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    // Set up a handler for when the request finishes
    xhr.onload = function () {
        if (xhr.status === 200) {
            // The request was successful
            var responseData = JSON.parse(xhr.responseText);
            console.log(responseData);
            if (responseData.status === 'success') {
                // Store the API key in DOM storage
                document.cookie = "apiKey=" + responseData.data.apikey + "; path=/; max-age=86400";
                document.cookie = "email=" + responseData.data.email + "; path=/; max-age=86400";
                // Redirect the user to index.php
                window.location.href = 'index.php'; // Will redirect to products page when products page is created
            } else {
                // Handle error
                alert('Error: ' + responseData.message);
            }
        } else {
            // The request failed
            console.error('Request failed. Status: ' + xhr.responseText);
        }
    };

    // Set up a handler for errors
    xhr.onerror = function () {
        console.error('Request failed ' + xhr.responseText);
    };

    // Send the request with the JSON data
    xhr.send(JSON.stringify(data));
}

function getCookie(name) {
    let cname = name + "=";
    let decodedCookie = decodeURIComponent(document.cookie);
    let ca = decodedCookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i].trim();
        if (c.indexOf(cname) === 0) {
            return c.substring(cname.length, c.length);
        }
    }
    return "";
}

function applySavedTheme() {
    const savedTheme = getCookie("theme");
    if (savedTheme === "dark") {
        document.body.classList.add("dark");
    } else {
        document.body.classList.remove("dark");
    }
}

window.addEventListener("load", applySavedTheme);

