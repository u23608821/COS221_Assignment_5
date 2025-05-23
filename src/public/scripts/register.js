function submitReg() {
    // Captcha Stuff 
    const captchaResponse = grecaptcha.getResponse();
    if (!captchaResponse) {
        alert("Please complete the reCAPTCHA verification first.");
        return;
    }

    console.log("submitReg function called");

    const firstName = document.getElementById("fname").value;
    const lastName = document.getElementById("lname").value;
    const email = document.getElementById("email").value;
    const password = document.getElementById("password").value;

    console.log("Form values:", { firstName, lastName, email, password });

    // Validation - Check if all required fields exist
    if (!firstName || !lastName || !email || !password) {
        alert('All fields are required.');
        console.error('All fields are required.');
        return;
    }

    // Name validation - letters only, at least 2 characters
    const nameRegex = /^[a-zA-Z-' ]*$/;
    if (!nameRegex.test(firstName) || firstName.length < 2) {
        alert('First name can contain only letters and must be at least 2 characters.');
        console.error('First name can contain only letters and must be at least 2 characters.');
        return;
    }

    // Last name validation - letters only, at least 2 characters
    if (!nameRegex.test(lastName) || lastName.length < 2) {
        alert('Last name can contain only letters and must be at least 2 characters.');
        console.error('Last name can contain only letters and must be at least 2 characters.');
        return;
    }

    // Email validation - simplified version
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email.toLowerCase())) {
        alert('Invalid email address.');
        console.error('Invalid email address.');
        return;
    }

    // Password validation with simpler regex
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>])[A-Za-z\d!@#$%^&*(),.?":{}|<>]{8,}$/;
    if (!passwordRegex.test(password)) {
        alert('Password must be at least 8 characters, contain upper and lower case letters, at least one digit, and one special symbol.');
        console.error('Password must be at least 8 characters, contain upper and lower case letters, at least one digit, and one special symbol.');
        return;
    }

    const payload = {
        type: "Register",
        name: firstName,
        surname: lastName,
        email: email,
        password: password,
        recaptcha_token: captchaResponse
    };

    console.log("Sending payload:", JSON.stringify(payload));

    // Send the data to the server
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'https://wheatley.cs.up.ac.za/u24634434/COS221/api.php', true);
    xhr.setRequestHeader("Content-Type", "application/json");
    xhr.setRequestHeader("Authorization", "Basic " + btoa(WHEATLEY_USERNAME + ":" + WHEATLEY_PASSWORD));

    xhr.onreadystatechange = function () {
        console.log("XHR state change:", xhr.readyState, xhr.status);

        if (xhr.readyState == 4) {
            console.log("Response received:", xhr.status);
            console.log("Response text:", xhr.responseText);

            if (xhr.status == 200) {
                try {
                    // Handle mixed responses that might contain both text and JSON
                    let responseText = xhr.responseText;
                    // Check if there's a JSON part in the response
                    let jsonStartIdx = responseText.indexOf('{');
                    if (jsonStartIdx >= 0) {
                        responseText = responseText.substring(jsonStartIdx);
                    }

                    const response = JSON.parse(responseText);
                    console.log("Parsed response:", response);

                    if (response.status === 'success') {
                        alert("The registration of your new account was successful! You can now proceed to the login page to access your account.");
                        window.location.href = 'login.php'; // Changed to .php extension
                    } else {
                        alert('Registration failed: ' + (response.message || 'Please try again.'));
                        console.error('Registration failed:', response);
                    }
                } catch (e) {
                    console.error("Error parsing response:", e, xhr.responseText);
                    alert("Error processing response from server.");
                }
            } else {
                console.error("Server returned error status:", xhr.status);
                try {
                    const errorData = JSON.parse(xhr.responseText);
                    console.error("Error details:", errorData);
                    alert("Server error: " + (errorData.message || xhr.status));
                } catch (e) {
                    alert("Server error: " + xhr.status);
                }
            }
        }
    };

    xhr.onerror = function (e) {
        console.error('Network Error', e);
        alert('Network Error: Could not connect to the server');
    };

    xhr.send(JSON.stringify(payload));
    console.log("Request sent to server");
}