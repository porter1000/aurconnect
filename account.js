// Signin.js
function handleSignIn(form) {
    // Prevent the default form submission
    event.preventDefault();

    // Extract user input from the form
    const email = form.querySelector('#email').value;
    const password = form.querySelector('#password').value;

    // Create a FormData object to send data to the server
    const formData = new FormData();
    formData.append('email', email);
    formData.append('password', password);

    // Send a POST request to your sign-in PHP script
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'signin.php', true);
    xhr.onload = function () {
        if (xhr.status === 200) {
            // Sign-in was successful, handle the response
            const response = JSON.parse(xhr.responseText);

            // Check the response for success or error
            if (response.success) {
                // After successful sign-in, you can redirect or perform other actions
                window.location.href = 'main_page.php'; // Replace with your main page URL
            } else {
                // Handle sign-in failure here
                console.error('Sign-in failed:', response.message);
            }
        } else {
            // Handle AJAX request error
            console.error('Error:', xhr.status, xhr.statusText);
        }
    };
    xhr.onerror = function () {
        // Handle AJAX request error
        console.error('Request failed');
    };
    xhr.send(formData);

    return false; // Prevent form submission
}
