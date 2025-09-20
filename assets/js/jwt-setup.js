document.addEventListener('DOMContentLoaded', function() {
	'use strict';

	const configDisplay = document.getElementById('jwt-config-display');
	const copyConfigBtn = document.getElementById('copy-config');
	const generateBtn = document.getElementById('generate-new-key');
	const copyMessage = document.getElementById('copy-message');

	// Generate initial key on page load
	generateNewKey();

	// Copy config line to clipboard
	copyConfigBtn.addEventListener('click', function() {
		const plainText = configDisplay.textContent;
		copyToClipboard(plainText);
	});

	// Generate new key
	generateBtn.addEventListener('click', generateNewKey);

	// Generate new key function
	function generateNewKey() {
		const newSecretKey = generateSecretKey();
		console.log('Generated key length:', newSecretKey.length, 'Key:', newSecretKey);
		
		// Escape HTML characters in the secret key
		const escapedKey = escapeHtml(newSecretKey);
		
		// Build highlighted HTML directly to avoid regex issues
		const highlightedCode = '<span class="php-function">define</span>( ' +
								'<span class="php-constant">\'COCART_JWT_AUTH_SECRET_KEY\'</span>, ' +
								'<span class="php-string">\'' + escapedKey + '\'</span> );';
		
		configDisplay.innerHTML = '<code>' + highlightedCode + '</code>';
	}

	// Escape HTML characters
	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	// Generate a secure secret key
	function generateSecretKey() {
		const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+-=[]{}|;:,.<>?';
		const length = 64;
		let result = '';
		
		// Use crypto.getRandomValues for cryptographically secure random numbers
		if (window.crypto && window.crypto.getRandomValues) {
			const array = new Uint8Array(length);
			window.crypto.getRandomValues(array);
			
			for (let i = 0; i < length; i++) {
				result += chars[array[i] % chars.length];
			}
		} else {
			// Fallback for older browsers
			for (let i = 0; i < length; i++) {
				result += chars[Math.floor(Math.random() * chars.length)];
			}
		}
		
		return result;
	}

	// Copy to clipboard function
	function copyToClipboard(text) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			// Modern clipboard API
			navigator.clipboard.writeText(text).then(function() {
				showCopyMessage();
			}).catch(function() {
				alert(cocart_jwt_setup.copy_failed);
			});
		} else {
			// Fallback for older browsers
			fallbackCopyToClipboard(text);
		}
	}

	// Fallback copy method
	function fallbackCopyToClipboard(text) {
		const tempTextarea = document.createElement('textarea');
		tempTextarea.value = text;
		tempTextarea.style.position = 'fixed';
		tempTextarea.style.opacity = '0';
		document.body.appendChild(tempTextarea);
		tempTextarea.select();
		
		try {
			document.execCommand('copy');
			showCopyMessage();
		} catch (err) {
			alert(cocart_jwt_setup.copy_failed);
		}
		
		document.body.removeChild(tempTextarea);
	}

	// Show copy message
	function showCopyMessage() {
		copyMessage.style.display = 'inline';
		setTimeout(function() {
			copyMessage.style.display = 'none';
		}, 2000);
	}
});