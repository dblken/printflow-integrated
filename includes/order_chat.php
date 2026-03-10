<?php
/**
 * Shared Order Chat Component
 * PrintFlow - Order Chat System
 */
?>
<!-- Chat Modal -->
<div id="chatModal" class="chat-modal-overlay">
    <div class="chat-container">
        <div class="chat-header">
            <div>
                <h3 id="chatOrderTitle">Order #—</h3>
                <div class="status-indicator">
                    <span id="partnerStatusDot" class="dot dot-offline"></span>
                    <span id="partnerStatusText">Offline</span>
                </div>
            </div>
            <button onclick="closeOrderChat()" style="background:none; border:none; color:white; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        
        <div id="chatMessages" class="chat-messages">
            <!-- Messages load here -->
        </div>

        <div id="chatQuickActions" class="chat-quick-actions" style="display:none; padding:10px 20px; border-top:1px solid #f1f5f9; background:#fff; gap:10px; flex-wrap:wrap;">
            <!-- Contextual buttons -->
        </div>

        <div id="typingIndicator" class="typing-indicator" style="visibility: hidden;">
            Partner is typing...
        </div>

        <div class="chat-input-area">
            <label class="chat-btn">
                <input type="file" id="chatImageInput" accept="image/*" style="display:none;">
                <span title="Send Image">🖼️</span>
            </label>
            <input type="text" id="chatTextInput" class="chat-input" placeholder="Type a message..." autocomplete="off">
            <button id="chatSendBtn" class="chat-btn" title="Send (Enter)">
                <span style="font-size: 1.2rem;">▶️</span>
            </button>
        </div>
    </div>
</div>

<!-- Image Lightbox -->
<div id="chatLightbox" class="chat-lightbox" onclick="this.style.display='none'">
    <img id="chatLightboxImg" src="" alt="Enlarged design">
</div>

<script>
let currentChatOrderId = null;
let lastMessageId = 0;
let chatPollingInterval = null;
let typingTimeout = null;
let isPartnerTyping = false;
let chatSound = new Audio('https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3'); // Fallback public sound

function openOrderChat(orderId, headerTitle) {
    currentChatOrderId = orderId;
    lastMessageId = 0;
    document.getElementById('chatOrderTitle').innerText = headerTitle;
    document.getElementById('chatMessages').innerHTML = '';
    document.getElementById('chatModal').style.display = 'flex';
    document.getElementById('chatTextInput').focus();
    
    // Setup Quick Actions based on page context
    const quickActions = document.getElementById('chatQuickActions');
    quickActions.innerHTML = '';
    
    if (window.location.href.includes('/customer/')) {
        quickActions.style.display = 'flex';
        quickActions.innerHTML = `
            <button onclick="window.location.href='order_details.php?id=${orderId}&pay=1'" style="background:#10b981; color:white; border:none; padding:6px 12px; border-radius:8px; font-size:0.8rem; font-weight:700; cursor:pointer;">💳 Pay Now</button>
            <button onclick="window.location.reload()" style="background:#f3f4f6; color:#1f2937; border:none; padding:6px 12px; border-radius:8px; font-size:0.8rem; font-weight:700; cursor:pointer;">🔄 Refresh Details</button>
        `;
    } else if (window.location.href.includes('/staff/')) {
        quickActions.style.display = 'flex';
        quickActions.innerHTML = `
            <button onclick="if(typeof openRevisionModal === 'function') { openRevisionModal(); } else { window.location.href='order_details.php?id=${orderId}'; }" style="background:#d97706; color:white; border:none; padding:6px 12px; border-radius:8px; font-size:0.8rem; font-weight:700; cursor:pointer;">📋 Request Revision</button>
            <button onclick="window.location.reload()" style="background:#f3f4f6; color:#1f2937; border:none; padding:6px 12px; border-radius:8px; font-size:0.8rem; font-weight:700; cursor:pointer;">🔄 Refresh Details</button>
        `;
    } else {
        quickActions.style.display = 'none';
    }

    fetchMessages();
    
    // Start polling
    if (chatPollingInterval) clearInterval(chatPollingInterval);
    chatPollingInterval = setInterval(fetchMessages, 3000);
}

function closeOrderChat() {
    document.getElementById('chatModal').style.display = 'none';
    clearInterval(chatPollingInterval);
    currentChatOrderId = null;
}

async function fetchMessages() {
    if (!currentChatOrderId) return;
    
    try {
        const response = await fetch(`/printflow/api/chat/fetch_messages.php?order_id=${currentChatOrderId}&last_id=${lastMessageId}`);
        const data = await response.json();
        
        if (data.success) {
            if (data.messages.length > 0) {
                const chatMessages = document.getElementById('chatMessages');
                data.messages.forEach(msg => {
                    appendMessage(msg);
                    lastMessageId = msg.id;
                });
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Play sound if new messages from partner
                const hasNewPartnerMsg = data.messages.some(m => !m.is_self);
                if (hasNewPartnerMsg) chatSound.play().catch(e => console.log('Audio play blocked'));
            }
            
            updatePartnerStatus(data.partner);
        }
    } catch (error) {
        console.error('Fetch error:', error);
    }
}

function appendMessage(msg) {
    const container = document.createElement('div');
    container.className = 'chat-bubble-container ' + (msg.is_self ? 'self' : 'other');
    
    let contentHtml = '';
    if (msg.message_type === 'image' || msg.image_path) {
        contentHtml += `<img src="${msg.image_path}" class="chat-image" onclick="showLightbox('${msg.image_path}')">`;
    }
    
    if (msg.message) {
        contentHtml += `<div class="chat-bubble">${escapeHtml(msg.message)}</div>`;
    }
    
    contentHtml += `<div class="chat-time">${msg.created_at}</div>`;
    
    if (msg.is_self && msg.is_seen) {
        contentHtml += `<div class="chat-seen">Seen</div>`;
    }
    
    container.innerHTML = contentHtml;
    document.getElementById('chatMessages').appendChild(container);
}

function updatePartnerStatus(status) {
    const dot = document.getElementById('partnerStatusDot');
    const text = document.getElementById('partnerStatusText');
    const typing = document.getElementById('typingIndicator');
    
    if (status.is_online) {
        dot.className = 'dot dot-online';
        text.innerText = 'Online';
    } else {
        dot.className = 'dot dot-offline';
        text.innerText = 'Offline';
    }
    
    typing.style.visibility = status.is_typing ? 'visible' : 'hidden';
}

async function sendMessage() {
    const input = document.getElementById('chatTextInput');
    const message = input.value.trim();
    if (!message && !document.getElementById('chatImageInput').files[0]) return;
    
    const formData = new FormData();
    formData.append('order_id', currentChatOrderId);
    formData.append('message', message);
    
    const imageFile = document.getElementById('chatImageInput').files[0];
    if (imageFile) formData.append('image', imageFile);
    
    input.value = '';
    document.getElementById('chatImageInput').value = '';
    
    try {
        const response = await fetch('/printflow/api/chat/send_message.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            fetchMessages(); // Immediately pull back my message
        } else {
            alert(data.error || 'Failed to send message');
        }
    } catch (error) {
        console.error('Send error:', error);
    }
}

function handleTyping() {
    if (!currentChatOrderId) return;
    
    const formData = new FormData();
    formData.append('order_id', currentChatOrderId);
    formData.append('is_typing', 1);
    
    fetch('/printflow/api/chat/status.php', { method: 'POST', body: formData });
    
    if (typingTimeout) clearTimeout(typingTimeout);
    typingTimeout = setTimeout(() => {
        const stopData = new FormData();
        stopData.append('order_id', currentChatOrderId);
        stopData.append('is_typing', 0);
        fetch('/printflow/api/chat/status.php', { method: 'POST', body: stopData });
    }, 3000);
}

function showLightbox(src) {
    document.getElementById('chatLightboxImg').src = src;
    document.getElementById('chatLightbox').style.display = 'flex';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Event Listeners
document.getElementById('chatTextInput').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') sendMessage();
    else handleTyping();
});

document.getElementById('chatSendBtn').addEventListener('click', sendMessage);

document.getElementById('chatImageInput').addEventListener('change', function() {
    if (this.files.length > 0) {
        sendMessage(); // Auto-send on file pick
    }
});
</script>
