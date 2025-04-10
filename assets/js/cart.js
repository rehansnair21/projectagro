// Cart operations
document.addEventListener('DOMContentLoaded', function() {
    // Update quantity
    const updateQuantity = async (productId, newQuantity) => {
        try {
            const response = await fetch('update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: newQuantity
                })
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    updateCartDisplay();
                    return true;
                }
            }
            throw new Error('Failed to update cart');
        } catch (error) {
            console.error('Error:', error);
            return false;
        }
    };

    // Remove item from cart
    const removeFromCart = async (productId) => {
        try {
            const response = await fetch('remove_from_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId
                })
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    const itemElement = document.querySelector(`[data-product-id="${productId}"]`);
                    if (itemElement) {
                        itemElement.remove();
                        updateCartTotal();
                    }
                    return true;
                }
            }
            throw new Error('Failed to remove item');
        } catch (error) {
            console.error('Error:', error);
            return false;
        }
    };

    // Update cart display
    const updateCartDisplay = () => {
        const cartItems = document.querySelectorAll('.cart-item');
        let total = 0;

        cartItems.forEach(item => {
            const quantity = parseInt(item.querySelector('.quantity-input').value);
            const price = parseFloat(item.querySelector('[data-price]').dataset.price);
            const itemTotal = quantity * price;
            
            item.querySelector('.item-total').textContent = `₹${itemTotal.toFixed(2)}`;
            total += itemTotal;
        });

        updateCartTotal(total);
    };

    // Update cart total
    const updateCartTotal = (total) => {
        const cartTotal = document.getElementById('cart-total');
        if (cartTotal) {
            cartTotal.textContent = `₹${total.toFixed(2)}`;
        }
    };

    // Event listeners
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', async (e) => {
            const productId = e.target.closest('.cart-item').dataset.productId;
            const newQuantity = parseInt(e.target.value);
            
            if (newQuantity > 0) {
                await updateQuantity(productId, newQuantity);
            } else {
                e.target.value = 1;
                await updateQuantity(productId, 1);
            }
        });
    });

    document.querySelectorAll('.remove-item').forEach(button => {
        button.addEventListener('click', async (e) => {
            const productId = e.target.closest('.cart-item').dataset.productId;
            await removeFromCart(productId);
        });
    });
});
