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



function submitReg() {
    console.log("submitReg function called");
    // Get full name and split into name and surname
    const fullName = document.getElementById("name")?.value.trim() || "";
    const nameParts = fullName.split(" ").filter(part => part.length > 0);

    if (nameParts.length < 2) {
        alert("Please enter both your first name and surname.");
        return;
    }

    const surname = nameParts.pop(); // Last word is the surname
    const name = nameParts.join(" "); // Everything else is the first name

    const email = document.getElementById("email")?.value.trim() || "";
    const password = document.getElementById("password")?.value.trim() || "";
    const user_type = document.getElementById("accountType")?.value.trim() || "";

    console.log("Form values:", { name, surname, email, password: "****", user_type });

    // Validation - Check if all required fields exist
    if (!name || !surname || !email || !password || !user_type) {
        alert('All fields are required.');
        console.error('All fields are required.');
        return;
    }

    // Name validation - letters only, at least 2 characters
    const nameRegex = /^[a-zA-Z-' ]*$/;
    if (!nameRegex.test(name) || name.trim().length < 2) {
        alert('Name can contain only letters and must be at least 2 characters.');
        console.error('Name can contain only letters and must be at least 2 characters.');
        return;
    }

    // Surname validation - letters only, at least 2 characters
    if (!nameRegex.test(surname) || surname.trim().length < 2) {
        alert('Surname can contain only letters and must be at least 2 characters.');
        console.error('Surname can contain only letters and must be at least 2 characters.');
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
        name,
        surname,
        phone_number: null,
        email,
        password,
        street_number: null,
        street_name: null,
        suburb: null,
        city: null,
        zip_code: null,
        user_type
    };

    console.log("Sending payload:", JSON.stringify(payload));

    // Send the data to the server
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'https://wheatley.cs.up.ac.za/u24634434/COS221/api.php', true);
    xhr.setRequestHeader("Content-Type", "application/json");  // Add this line

    // We need auth credentials for Wheatley server
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
                        window.location.href = 'login.html'; // Correct path based on your directory structure
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






// function buildPasswordRegex({ minLength = 8, upper = true, lower = true, digit = true, special = true } = {}) {
//     let pattern = "^";
//     if (lower) pattern += "(?=.*[a-z])";
//     if (upper) pattern += "(?=.*[A-Z])";
//     if (digit) pattern += "(?=.*\\d)";
//     if (special) pattern += "(?=.*[\\W_])";
//     pattern += `.{${minLength
//         },}$`;
//     return new RegExp(pattern);
// }

// window.addEventListener("load", () => {
//     applySavedTheme();

//     const form = document.querySelector("form");
//     if (!form) return;

//     form.addEventListener("submit", async function (e) {
//         e.preventDefault();

//         // Get full name and split into name and surname
//         const fullName = document.getElementById("name")?.value.trim() || "";
//         const nameParts = fullName.split(" ").filter(part => part.length > 0);

//         if (nameParts.length < 2) {
//             alert("Please enter both your first name and surname.");
//             return;
//         }

//         const surname = nameParts.pop(); // Last word is the surname
//         const name = nameParts.join(" "); // Everything else is the first name

//         const email = document.getElementById("email")?.value.trim() || "";
//         const password = document.getElementById("password")?.value.trim() || "";
//         const user_type = document.getElementById("accountType")?.value.trim() || "";

//         if (!fullName) {
//             alert("Please fill in your full name.");
//             return;
//         }
//         if (!email) {
//             alert("Please fill in your email.");
//             return;
//         }
//         const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
//         if (!emailRegex.test(email)) {
//             alert("Invalid email format!");
//             return;
//         }
//         if (!password) {
//             alert("Please create a password.");
//             return;
//         }
//         const passwordRequirements = {
//             minLength: 8,
//             upper: true,
//             lower: true,
//             digit: true,
//             special: true
//         };
//         const passwordRegex = buildPasswordRegex(passwordRequirements);
//         if (!passwordRegex.test(password)) {
//             alert("Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.");
//             return;
//         }

//         if (!user_type) {
//             alert("Please select a user type.");
//             return;
//         }

//         // Set required fields and null for optional fields
//         const payload = {
//             type: "Register",
//             name,
//             surname,
//             phone_number: null,
//             email,
//             password,
//             street_number: null,
//             street_name: null,
//             suburb: null,
//             city: null,
//             zip_code: null,
//             user_type
//         };

//         console.log('Sending registration request:', payload);
//         const xhr = new XMLHttpRequest();

//         xhr.open('POST', 'https://wheatley.cs.up.ac.za/u24634434/COS221/api.php', true);
//         xhr.setRequestHeader('Content-Type', 'application/json');
//         xhr.setRequestHeader("Authorization", "Basic " + btoa(WHEATLEY_USERNAME + ":" + WHEATLEY_PASSWORD));

//         xhr.onreadystatechange = function () {
//             if (xhr.readyState == 4 && xhr.status == 200) {
//                 console.log(xhr.responseText);
//                 const response = JSON.parse(xhr.responseText);
//                 if (response.status === 'success') {
//                     window.location.href = 'login.html'; // Redirect to login page after successful registration
//                 } else {
//                     console.error('Registration failed. Please try again.');
//                 }
//             } else {
//                 console.error('An error occurred. Please try again later.');
//             }

//         };

//         xhr.onerror = function () {
//             console.error('Request failed');
//             alert("Could not connect to the server. Please check your connection and try again.");
//         };

//         // Send the request
//         xhr.send(JSON.stringify(payload));
//     });
// });