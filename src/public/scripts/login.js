function clickLogin() {
    // Captcha must be completed first
    const captchaResponse = grecaptcha.getResponse();
    if (!captchaResponse) {
        alert("Please complete the reCAPTCHA verification first.");
        return;
    }

    var emailInput = document.getElementById("username").value;
    var passwordInput = document.getElementById("password").value;

    var data = {
        type: "Login",
        email: emailInput,
        password: passwordInput,
        recaptcha_token: captchaResponse
    };

    // Create a new XMLHttpRequest object
    var xhr = new XMLHttpRequest();

    // Configure the request
    xhr.open('POST', 'https://wheatley.cs.up.ac.za/u24634434/COS221/api.php', true);

    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader("Authorization", "Basic " + btoa(WHEATLEY_USERNAME + ":" + WHEATLEY_PASSWORD));

    // Set up a handler for when the request finishes
    xhr.onload = function () {
        if (xhr.status === 200 || xhr.status === 201) {
            // The request was successful
            // console.log("SUCCESS");
            var responseData = JSON.parse(xhr.responseText);
            console.log(responseData);
            if (responseData.status === 'success') {
                console.log('Success response structure:', responseData);

                // Extract API key from data object (not message)
                if (responseData.data && responseData.data.apikey) {
                    // Store the API key in localStorage instead of cookies
                    localStorage.setItem("apiKey", responseData.data.apikey);

                    // Store email if available
                    if (responseData.data.email) {
                        localStorage.setItem("email", responseData.data.email);
                    }

                    // Store user_type if available
                    if (responseData.data.user_type) {
                        localStorage.setItem("user_type", responseData.data.user_type);
                        console.log('User type stored:', responseData.data.user_type);

                        // Redirect based on user type (case-insensitive comparison)
                        const userType = responseData.data.user_type.toLowerCase();
                        if (userType === 'customer') {
                            window.location.href = 'products.php';
                        } else if (userType === 'admin') {
                            window.location.href = 'Admin.php';
                        } else {
                            console.error('Unknown user type:', responseData.data.user_type);
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

// Replace getCookie with localStorage functions
function getLocalStorage(name) {
    return localStorage.getItem(name) || "";
}

function setLocalStorage(name, value) {
    localStorage.setItem(name, value);
}

function removeLocalStorage(name) {
    localStorage.removeItem(name);
}

// Validate the captcha. It must be completed before the user tries to logon. 
function validateCaptcha() {
    const response = grecaptcha.getResponse();
    if (response.length === 0) {
        alert("Please complete the reCAPTCHA verification first.");
        return false;
    }
    return true;
}