// const checkUserIpCheckbox = document.body.querySelector('input[id="check_user_ip_checkbox"]');
const
    userIpCheckbox = document.getElementById('check_user_ip'),
    addIpButton = document.getElementById('add_current_ip_to_whitelist')
;

userIpCheckbox.addEventListener('change', toggleCheckingIp);
addIpButton.addEventListener('click', addCurrentIpToWhiteList);

// Enable or disable the verification of the user's IP address during the authentication process via an AJAX call
function toggleCheckingIp() {
    const
        userIpCheckboxLabel = document.querySelector('label[for="check_user_ip"]'),
        urlController = this.getAttribute('data-url'),
        fetchOptions = {
            body: JSON.stringify(userIpCheckbox.checked),
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            method: 'POST'
        }
    ;

    fetch(urlController, fetchOptions)
        .then(response => response.json())
        .then(({isGuardCheckingIp}) => userIpCheckboxLabel.textContent = isGuardCheckingIp ? 'Active' : 'Inactive')
        .catch(error => console.error(error));
}

// Add current IP address to whitelist via AJAX call
function addCurrentIpToWhiteList() {
    const
        userIpAddresses = document.getElementById('user_ip_addresses'),
        controllerUrl = this.getAttribute('data-url'),
        fetchOptions = {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            method: 'GET'
        }
    ;

    fetch(controllerUrl, fetchOptions)
        .then(response => response.json())
        .then(({user_ip}) => {
            if (userIpAddresses.textContent === '') {
                userIpAddresses.textContent = user_ip;
            } else {
                if (!userIpAddresses.textContent.includes(user_ip)) {
                    userIpAddresses.textContent += ` | ${user_ip}`;
                }
            }
        })
        .catch(error => console.error(error));
}
