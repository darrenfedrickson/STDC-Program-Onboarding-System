<?php
// user/index.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireLogin();
if (isAdmin()) {
    redirect('/admin/index.php');
}

$user_id = $_SESSION['user_id'];

// Get applied programs
$appliedStmt = $pdo->prepare("
    SELECT p.*, r.application_status, r.created_at as applied_at
    FROM programs p
    JOIN registrations r ON p.id = r.program_id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$appliedStmt->execute([$user_id]);
$appliedPrograms = $appliedStmt->fetchAll();

// Get IDs of applied programs to exclude from available list
$appliedIds = array_column($appliedPrograms, 'id');

// Get active programs not yet applied to
if (!empty($appliedIds)) {
    $placeholders = implode(',', array_fill(0, count($appliedIds), '?'));
    $availStmt = $pdo->prepare("SELECT p.*, (SELECT COUNT(*) FROM registrations r WHERE r.program_id = p.id) as registered_count FROM programs p WHERE p.status = 'active' AND p.id NOT IN ($placeholders) ORDER BY p.created_at DESC");
    $availStmt->execute($appliedIds);
} else {
    $availStmt = $pdo->query("SELECT p.*, (SELECT COUNT(*) FROM registrations r WHERE r.program_id = p.id) as registered_count FROM programs p WHERE p.status = 'active' ORDER BY p.created_at DESC");
}
$availablePrograms = $availStmt->fetchAll();
?>

<?php require dirname(__DIR__) . '/includes/header.php'; ?>

<div class="mb-4" style="display: flex; justify-content: space-between; align-items: flex-start;">
    <div>
        <h1>User Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>.</p>
    </div>
    <div style="display: flex; align-items: center; gap: 1rem;">
        <div style="position: relative; width: 46px; height: 46px;">
            <a href="<?php echo BASE_URL; ?>/user/applications.php" class="notification-btn">
                <div class="notification-icon-wrapper">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    <?php if (count($appliedPrograms) > 0): ?>
                        <span class="notification-badge"><?php echo count($appliedPrograms); ?></span>
                    <?php endif; ?>
                </div>
                <span class="notification-text">View Your Applications</span>
            </a>
        </div>
    </div>
</div>

<!-- Floating Action Button -->
<button type="button" id="btn-toggle-chatbot" style="position: fixed; bottom: 30px; right: 30px; width: 60px; height: 60px; border-radius: 50%; background-color: var(--primary-red); color: white; border: none; box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4); cursor: pointer; z-index: 1000; display: flex; justify-content: center; align-items: center; transition: transform 0.2s;">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path><path d="M9 10h.01"></path><path d="M15 10h.01"></path></svg>
</button>

<!-- AI Chatbot Popup Container (Hidden by default) -->
<div id="ai-chat-container" class="card" style="display: none; position: fixed; bottom: 100px; right: 30px; width: 380px; max-width: 90vw; z-index: 1000; box-shadow: 0 10px 25px rgba(0,0,0,0.2); border-top: 5px solid var(--primary-red); padding: 0;">
    <!-- Header -->
    <div style="background: var(--bg-surface); padding: 15px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; border-radius: 8px 8px 0 0;">
        <h3 style="margin: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--primary-red)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg> STDC Concierge
        </h3>
        <button id="btn-close-chatbot" style="background: none; border: none; color: var(--text-light); cursor: pointer; font-size: 1.5rem; line-height: 1;">&times;</button>
    </div>
    
    <!-- Body -->
    <div style="padding: 15px;">
        <p class="text-light" style="font-size: 0.85rem; margin-bottom: 15px;">I can help you find the right program and assist you with your registration automatically.</p>
        
        <div id="chat-messages" style="background: #f8f9fa; border-radius: 8px; padding: 15px; height: 300px; overflow-y: auto; margin-bottom: 15px; border: 1px solid var(--border-color); display: flex; flex-direction: column; gap: 10px;">
            <div style="align-self: flex-start; background: white; padding: 10px 14px; border-radius: 15px; border-bottom-left-radius: 4px; max-width: 90%; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid var(--border-color); font-size: 0.9rem;">
                Hello <?php echo htmlspecialchars($_SESSION['full_name']); ?>! Are you looking for a specific program, or would you like me to suggest one?
            </div>
        </div>
        
        <div style="display: flex; gap: 8px;">
            <input type="text" id="chat-input" class="form-control" placeholder="Type here..." style="border-radius: 20px; font-size: 0.9rem;">
            <button type="button" id="chat-send-btn" class="btn btn-primary" style="border-radius: 50%; width: 40px; height: 40px; padding: 0; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
            </button>
        </div>
    </div>
</div>

<!-- Available Programs Section -->
<div class="mb-4">
    <h3 style="margin-bottom: 0.5rem;">Available Programs</h3>
    <p class="text-light mb-4">Programs you can apply for right now.</p>
    
    <?php if (count($availablePrograms) > 0): ?>
        <div class="glass-grid">
            <?php foreach ($availablePrograms as $prog): ?>
                <div class="glass-card">
                    <div class="glass-card-header">
                        <div class="glass-badges">
                            <span class="glass-badge"><?php echo htmlspecialchars(explode(' ', $prog['title'])[0]); ?></span>
                            <?php if (!empty($prog['intake_date'])): ?>
                                <span class="glass-badge"><?php echo htmlspecialchars($prog['intake_date']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="glass-logo-placeholder">
                            <img src="<?php echo BASE_URL; ?>/assets/img/LogoSTDC.png" alt="Logo">
                        </div>
                    </div>
                    
                    <h4 class="glass-card-title"><?php echo htmlspecialchars($prog['title']); ?></h4>
                    
                    <div class="glass-meta">
                        <?php if (!empty($prog['location'])): ?>
                            <div class="glass-meta-item">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                <span><?php echo htmlspecialchars($prog['location']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($prog['duration'])): ?>
                            <div class="glass-meta-item text-red">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                <span><?php echo htmlspecialchars($prog['duration']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <p class="glass-desc">
                        <?php 
                        $desc = htmlspecialchars($prog['description']);
                        echo strlen($desc) > 80 ? substr($desc, 0, 80) . '...' : $desc; 
                        ?>
                    </p>
                    
                    <div class="glass-buttons">
                        <button type="button" class="glass-btn glass-btn-outline" onclick="openChatForProgram('<?php echo htmlspecialchars(addslashes($prog['title']), ENT_QUOTES); ?>')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                            Ask Me
                        </button>
                        
                        <?php if ($prog['registered_count'] >= $prog['capacity']): ?>
                            <button class="glass-btn glass-btn-solid" style="background: var(--text-light); box-shadow: none; cursor: not-allowed;" disabled>Full</button>
                        <?php else: ?>
                            <a href="<?php echo BASE_URL; ?>/user/register.php?id=<?php echo $prog['id']; ?>" class="glass-btn glass-btn-solid">
                                Apply
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card mb-4">
            <p>No new active programs available at the moment.</p>
        </div>
    <?php endif; ?>
</div>



<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- AI CHATBOT LOGIC ---
    const btnToggleChat = document.getElementById('btn-toggle-chatbot');
    const btnCloseChat = document.getElementById('btn-close-chatbot');
    const aiChatContainer = document.getElementById('ai-chat-container');
    const chatInput = document.getElementById('chat-input');
    const chatSendBtn = document.getElementById('chat-send-btn');
    const chatMessages = document.getElementById('chat-messages');
    
    let chatHistory = [];
    let currentProgramId = 0; // 0 = Phase 1 (Selection), >0 = Phase 2 (Registration)
    
    function toggleChat() {
        if (aiChatContainer.style.display === 'none') {
            aiChatContainer.style.display = 'block';
            chatInput.focus();
        } else {
            aiChatContainer.style.display = 'none';
        }
    }
    
    // Make it globally accessible for the "Ask Me" buttons
    window.openChatForProgram = function(programTitle) {
        if (aiChatContainer.style.display === 'none') {
            toggleChat();
        }
        // Send a hidden message to the AI saying we want to ask about this specific program
        sendChatMessage(`I want to know more about the program "${programTitle}". Can you tell me about it?`);
    };
    
    btnToggleChat.addEventListener('click', toggleChat);
    btnCloseChat.addEventListener('click', toggleChat);
    
    function appendMessage(role, text) {
        const msgDiv = document.createElement('div');
        msgDiv.style.maxWidth = '85%';
        msgDiv.style.padding = '12px 18px';
        msgDiv.style.borderRadius = '20px';
        msgDiv.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
        msgDiv.style.wordWrap = 'break-word';
        
        if (role === 'user') {
            msgDiv.style.alignSelf = 'flex-end';
            msgDiv.style.background = '#e3f2fd';
            msgDiv.style.color = '#000';
            msgDiv.style.borderBottomRightRadius = '4px';
        } else {
            msgDiv.style.alignSelf = 'flex-start';
            msgDiv.style.background = 'white';
            msgDiv.style.color = '#000';
            msgDiv.style.borderBottomLeftRadius = '4px';
            msgDiv.style.border = '1px solid #e0e0e0';
        }
        
        // Convert basic markdown to html
        msgDiv.innerHTML = text
            .replace(/\n/g, '<br>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\* /g, '&bull; ');
        chatMessages.appendChild(msgDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    async function sendChatMessage(hiddenMessage = null) {
        const message = hiddenMessage !== null ? hiddenMessage : chatInput.value.trim();
        if (!message && hiddenMessage === null) return;
        
        if (hiddenMessage === null) {
            appendMessage('user', message);
            chatInput.value = '';
        }
        
        chatInput.disabled = true;
        chatSendBtn.disabled = true;
        
        // Add loading indicator
        const loadingDiv = document.createElement('div');
        loadingDiv.id = 'chat-loading';
        loadingDiv.style.alignSelf = 'flex-start';
        loadingDiv.innerHTML = '<span style="display:inline-block; animation: pulse 1.5s infinite; color: var(--primary-red);">●</span> Thinking...';
        loadingDiv.style.padding = '10px';
        loadingDiv.style.color = '#888';
        chatMessages.appendChild(loadingDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        try {
            const response = await fetch('<?php echo BASE_URL; ?>/user/chatbot_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    program_id: currentProgramId,
                    message: message,
                    history: chatHistory
                })
            });
            
            const data = await response.json();
            document.getElementById('chat-loading').remove();
            
            if (data.error) {
                appendMessage('model', 'Oops, something went wrong: ' + data.error);
            } else if (data.status === 'program_selected') {
                // Phase 1 -> Phase 2 Transition
                currentProgramId = data.program_id;
                appendMessage('model', 'Great choice! Give me just a second to pull up the registration form for that program...');
                
                // Clear history to start fresh for registration context
                chatHistory = []; 
                
                // Trigger an invisible initial message to kickstart the registration conversation
                setTimeout(() => {
                    sendChatMessage("I am ready to register. Please ask me for the first piece of information.");
                }, 1000);
                
            } else if (data.status === 'complete') {
                appendMessage('model', '🎉 Awesome! I have all the information I need. I am submitting your registration now...');
                
                // Dynamically build and submit the hidden form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?php echo BASE_URL; ?>/user/process_apply.php';
                
                const pInput = document.createElement('input');
                pInput.type = 'hidden';
                pInput.name = 'program_id';
                pInput.value = currentProgramId;
                form.appendChild(pInput);
                
                for (const key in data.data) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    // process_apply expects inputs to be named literally matching what the form would send
                    // which is exactly what our API instructed Gemini to output (custom_field_name)
                    input.name = key; 
                    
                    // Arrays (like checkboxes) need to be imploded or submitted as array.
                    // Actually, process_apply accepts single values or arrays if they are named properly.
                    // But if it's a string, we just pass it. 
                    input.value = Array.isArray(data.data[key]) ? data.data[key].join(', ') : data.data[key];
                    
                    form.appendChild(input);
                }
                
                document.body.appendChild(form);
                
                setTimeout(() => {
                    form.submit();
                }, 1500);
                
            } else {
                appendMessage('model', data.message);
                if (hiddenMessage === null) {
                    chatHistory.push({ role: 'user', text: message });
                }
                chatHistory.push({ role: 'model', text: data.message });
            }
        } catch (error) {
            document.getElementById('chat-loading')?.remove();
            appendMessage('model', 'Connection error. Please try again.');
            console.error(error);
        }
        
        chatInput.disabled = false;
        chatSendBtn.disabled = false;
        chatInput.focus();
    }
    
    chatSendBtn.addEventListener('click', () => sendChatMessage(null));
    chatInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendChatMessage(null);
        }
    });
});
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
