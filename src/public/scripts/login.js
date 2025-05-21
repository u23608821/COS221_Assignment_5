function clickLogin() {

    var emailInput = document.getElementById("username");
    var passwordInput = document.getElementById("password");

    var data = {
        type: "Login",
        email: emailInput.value,
        password: passwordInput.value
    };



    // Create a new XMLHttpRequest object
    var xhr = new XMLHttpRequest();

    // Configure the request
    xhr.open('POST', 'https://wheatley.cs.up.ac.za/u24634434/COS221/api.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader("Authorization", "Basic " + btoa(WHEATLEY_USERNAME + ":" + WHEATLEY_PASSWORD));

    // Set up a handler for when the request finishes
    xhr.onload = function () {
        if (xhr.status === 200) {
            // The request was successful
            var responseData = JSON.parse(xhr.responseText);
            console.log(responseData);
            if (responseData.status === 'success') {
                console.log('Success response structure:', responseData);

                // Based on the response structure, we know the data is in the message object
                if (responseData.message) {
                    // Store the API key
                    if (responseData.message.apikey) {
                        document.cookie = "apiKey=" + responseData.message.apikey;
                    } else {
                        console.error('API key not found in response message');
                        alert('Error: Unable to retrieve API key');
                        return;
                    }

                    // Store email if available
                    if (responseData.message.email) {
                        document.cookie = "email=" + responseData.message.email;
                    }

                    // Store user_type if available
                    if (responseData.message.user_type) {
                        document.cookie = "user_type=" + responseData.message.user_type;
                        console.log('User type stored:', responseData.message.user_type);

                        // Redirect based on user type
                        if (responseData.message.user_type === 'customer') {
                            window.location.href = 'products.html';
                        } else if (responseData.message.user_type === 'admin') {
                            window.location.href = 'Admin.html';
                        } else {
                            console.error('Unknown user type:', responseData.message.user_type);
                            alert('Error: Unknown user type');
                        }
                    } else {
                        console.log('No user_type found in response');
                        alert('Error: User type not specified');
                    }
                } else {
                    // Handle missing data or apikey
                    console.error('API key not found in response:', responseData);
                    alert('Error: Unable to retrieve API key. Please check the console for details.');
                }
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

