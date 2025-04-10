// Add to cart functionality
async function addToCart(productId, quantity = 1) {
    try {
        const response = await fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: quantity
            })
        });

        const data = await response.json();
        
        // Show message to user
        const messageDiv = document.createElement('div');
        messageDiv.className = `alert ${data.success ? 'alert-success' : 'alert-danger'}`;
        messageDiv.style.position = 'fixed';
        messageDiv.style.top = '20px';
        messageDiv.style.right = '20px';
        messageDiv.style.zIndex = '1000';
        messageDiv.style.padding = '1rem';
        messageDiv.style.borderRadius = '0.5rem';
        messageDiv.style.backgroundColor = data.success ? '#22c55e' : '#ef4444';
        messageDiv.style.color = 'white';
        messageDiv.textContent = data.message;

        document.body.appendChild(messageDiv);

        // Remove message after 3 seconds
        setTimeout(() => {
            messageDiv.remove();
        }, 3000);

        return data.success;
    } catch (error) {
        console.error('Error:', error);
        return false;
    }
}

// Add event listeners to all "Add to Cart" buttons
document.addEventListener('DOMContentLoaded', function() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', async (e) => {
            e.preventDefault();
            const productId = button.dataset.productId;
            const quantityInput = button.parentElement.querySelector('.quantity-input');
            const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
            
            const success = await addToCart(productId, quantity);
            if (success) {
                // Optionally update cart counter if you have one
                const cartCounter = document.querySelector('.cart-counter');
                if (cartCounter) {
                    const currentCount = parseInt(cartCounter.textContent);
                    cartCounter.textContent = currentCount + 1;
                }
            }
        });
    });
});
