<?php
// admin/index.php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

// Fetch overall stats
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$activePrograms = $pdo->query("SELECT COUNT(*) FROM programs WHERE status='active'")->fetchColumn();
$pendingRegistrations = $pdo->query("SELECT COUNT(*) FROM registrations WHERE application_status='pending'")->fetchColumn();

// Fetch AI Sessions
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id, title FROM ai_sessions WHERE user_id = ? ORDER BY updated_at DESC");
$stmt->execute([$userId]);
$ai_widget_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

require dirname(__DIR__) . '/includes/header.php';
?>

<div class="mb-4">
    <h1>Admin Dashboard</h1>
    <p>Welcome to the STDC Program Onboarding System</p>
</div>

<div class="grid grid-cols-3">
    <div class="stat-card">
        <span class="stat-title">Total Users</span>
        <span class="stat-value"><?php echo $totalUsers; ?></span>
    </div>
    <div class="stat-card">
        <span class="stat-title">Active Programs</span>
        <span class="stat-value"><?php echo $activePrograms; ?></span>
    </div>
    <div class="stat-card">
        <a href="<?php echo BASE_URL; ?>/admin/registrations.php"><span class="stat-title">Pending Registrations</span></a>
        <span class="stat-value"><?php echo $pendingRegistrations; ?></span>
    </div>
</div>

<style>
    /* Integrated Chatbot CSS - Futuristic Gemini Soft Theme */
    #aiWidget {
        display: flex;
        flex-direction: row;
        height: 600px;
        background: #f8f9fa;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
        border: 1px solid #e2e8f0;
        margin-top: 40px;
        overflow: hidden;
        font-family: 'Inter', sans-serif;
        color: #334155;
        transition: all 0.3s ease;
    }

    #aiWidget.ai-w-expanded {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        margin-top: 0;
        border-radius: 0;
        z-index: 99999;
        border: none;
    }

    /* Sidebar */
    .ai-w-sidebar {
        width: 280px;
        background: #f1f3f4;
        border-right: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        transition: width 0.3s ease;
    }

    .ai-w-sidebar.collapsed {
        width: 0;
        border-right: none;
        overflow: hidden;
    }

    .btn-sidebar-toggle {
        background: transparent;
        border: none;
        color: #64748b;
        cursor: pointer;
        padding: 6px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        transition: all 0.2s;
    }

    .btn-sidebar-toggle:hover {
        background: #e2e8f0;
        color: #1e293b;
    }

    .ai-w-sidebar-header {
        padding: 24px 20px;
        background: transparent;
    }

    .ai-w-sidebar-header h5 {
        background: linear-gradient(135deg, #4285F4, #9b72cb, #d96570);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight: 700;
        letter-spacing: -0.5px;
        margin: 0;
    }

    .btn-gemini-new {
        background: #ffffff;
        color: #4285F4;
        border: 1px solid #e2e8f0;
        border-radius: 30px;
        padding: 8px 16px;
        font-weight: 500;
        width: 100%;
        margin-top: 15px;
        transition: all 0.2s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
    }

    .btn-gemini-new:hover {
        box-shadow: 0 4px 12px rgba(66, 133, 244, 0.15);
        border-color: #4285F4;
        transform: translateY(-1px);
        background: #f8fafc;
    }

    .ai-w-session-list {
        flex: 1;
        overflow-y: auto;
        padding: 10px 0;
    }

    .ai-w-session {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 20px;
        color: #64748b;
        text-decoration: none;
        border-left: 3px solid transparent;
        font-size: 0.95rem;
        cursor: pointer;
        transition: background 0.2s;
    }

    .ai-w-session span.sess-title {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        flex: 1;
    }

    .ai-w-session:hover {
        background: #e2e8f0;
        color: #1e293b;
    }

    .ai-w-session.active {
        background: #dbeafe;
        border-left-color: #4285F4;
        color: #1e3a8a;
        font-weight: 500;
    }

    .btn-delete-chat {
        background: transparent;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 4px;
        display: none;
        border-radius: 4px;
    }

    .ai-w-session:hover .btn-delete-chat {
        display: block;
    }

    .btn-delete-chat:hover {
        color: #ef4444;
        background: #fee2e2;
    }

    /* Main Chat Area */
    .ai-w-main {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        background: #f8f9fa;
        position: relative;
    }

    .ai-w-header {
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border-bottom: 1px solid #e2e8f0;
        color: #1e293b;
        padding: 15px 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }

    .ai-w-header select {
        color: #1e293b !important;
        border: 1px solid #cbd5e1 !important;
        background: #ffffff !important;
    }

    .ai-w-history {
        flex: 1;
        min-width: 0;
        overflow-y: auto;
        padding: 30px;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    /* Bubbles */
    .ai-w-row {
        display: flex;
        width: 100%;
        margin: 0 auto;
        min-width: 0;
    }

    .ai-w-row.user {
        justify-content: flex-end;
    }

    .ai-w-row.ai {
        justify-content: flex-start;
    }

    .ai-w-bubble-user {
        background: #e2e8f0;
        color: #0f172a;
        padding: 14px 20px;
        border-radius: 20px 20px 4px 20px;
        font-size: 0.95rem;
        max-width: 80%;
    }

    .ai-w-bubble-ai {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 4px 20px 20px 20px;
        padding: 20px;
        width: 100%;
        min-width: 0;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        display: flex;
        gap: 16px;
    }

    .ai-w-avatar {
        background: linear-gradient(135deg, #4285F4, #9b72cb);
        color: white;
        border-radius: 50%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(66, 133, 244, 0.2);
    }

    .ai-w-avatar.thinking {
        animation: pulse 1.5s infinite ease-in-out;
    }

    @keyframes pulse {
        0% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(66, 133, 244, 0.4);
        }

        70% {
            transform: scale(1);
            box-shadow: 0 0 0 10px rgba(66, 133, 244, 0);
        }

        100% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(66, 133, 244, 0);
        }
    }

    .ai-w-content {
        flex: 1;
        min-width: 0;
        color: #334155;
        line-height: 1.6;
        font-size: 0.95rem;
        overflow-x: auto;
    }

    /* Glassmorphism Input Area */
    .ai-w-input-area {
        padding: 20px 30px;
        background: rgba(248, 249, 250, 0.8);
        backdrop-filter: blur(10px);
        border-top: 1px solid #e2e8f0;
    }

    .ai-w-input-wrap {
        background: #ffffff;
        border-radius: 30px;
        padding: 8px 12px 8px 24px;
        border: 1px solid #cbd5e1;
        display: flex;
        align-items: center;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        transition: all 0.3s ease;
    }

    .ai-w-input-wrap:focus-within {
        box-shadow: 0 4px 20px rgba(66, 133, 244, 0.1);
        border-color: #94a3b8;
    }

    .ai-w-input-wrap input {
        flex: 1;
        border: none;
        outline: none;
        background: transparent;
        padding: 8px 0;
        font-size: 1rem;
        color: #1e293b;
    }

    .ai-w-input-wrap input::placeholder {
        color: #94a3b8;
    }

    .ai-w-input-wrap button {
        background: #4285F4;
        color: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s, background 0.2s;
    }

    .ai-w-input-wrap button:hover {
        background: #3b82f6;
        transform: scale(1.05);
    }

    .ai-w-input-wrap button:disabled {
        background: #cbd5e1;
        color: #f1f5f9;
        transform: none;
        cursor: not-allowed;
    }

    /* Chart inside bubble */
    .ai-w-chart {
        position: relative;
        height: 350px;
        width: 100%;
        margin-top: 20px;
    }

    /* Table inside bubble */
    .ai-w-table {
        width: 100%;
        text-align: left;
        border-collapse: collapse;
        margin-top: 20px;
        font-size: 0.85rem;
    }

    .ai-w-table th {
        background: #f1f3f4;
        padding: 12px;
        border-bottom: 2px solid #cbd5e1;
        color: #475569;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
    }

    .ai-w-table td {
        padding: 12px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }

    /* Modal Styles */
    .export-modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(4px);
        z-index: 10000;
        align-items: center;
        justify-content: center;
    }

    .export-modal-content {
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        width: 90%;
        max-width: 800px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .export-modal-header {
        padding: 16px 24px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .export-modal-header h3 {
        margin: 0;
        color: #1e293b;
        font-size: 1.1rem;
    }

    .export-modal-body {
        padding: 24px;
        flex: 1;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .export-modal-preview-box {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: auto;
        padding: 20px;
        min-height: 300px;
    }

    .export-modal-controls {
        display: flex;
        gap: 16px;
        align-items: flex-end;
    }

    .export-input-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .export-input-group label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
    }

    .export-input-group input {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 0.9rem;
        width: 120px;
        outline: none;
    }

    .export-input-group input:focus {
        border-color: #4285F4;
    }

    .export-modal-footer {
        padding: 16px 24px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        background: #f8fafc;
    }
</style>

<div id="aiWidget">
    <div id="chatContextMenu" style="display:none; position:fixed; background:white; border:1px solid #e2e8f0; border-radius:8px; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1); padding:5px 0; z-index:9999; font-size:0.9rem; min-width:150px;">
        <div onclick="confirmDeleteMessage()" style="padding:10px 15px; cursor:pointer; color:#ef4444; display:flex; align-items:center; gap:8px; transition:background 0.2s;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='transparent'">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
            Delete Message
        </div>
    </div>

    <!-- Export Preview Modal -->
    <div id="chartExportModal" class="export-modal-overlay">
        <div class="export-modal-content">
            <div class="export-modal-header">
                <h3>Export Chart Preview</h3>
                <button onclick="closeExportModal()" style="background:none; border:none; cursor:pointer; color:#64748b;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div class="export-modal-body">
                <div class="export-modal-controls">
                    <div class="export-input-group">
                        <label>Width (px)</label>
                        <input type="number" id="exportWidth" value="1200" onchange="updatePreviewChart()">
                    </div>
                    <div class="export-input-group">
                        <label>Height (px)</label>
                        <input type="number" id="exportHeight" value="800" onchange="updatePreviewChart()">
                    </div>
                    <div style="font-size: 0.8rem; color: #64748b; margin-bottom: 8px;">
                        Adjusting these will re-render the chart dynamically to fit more labels or larger presentations.
                    </div>
                </div>
                <div class="export-modal-preview-box">
                    <div id="exportCanvasContainer" style="position:relative; width:1200px; height:800px; transform-origin: top left; transition: transform 0.2s;">
                        <canvas id="exportPreviewCanvas"></canvas>
                    </div>
                </div>
            </div>
            <div class="export-modal-footer">
                <button onclick="closeExportModal()" style="padding:8px 16px; border-radius:6px; border:1px solid #cbd5e1; background:white; color:#475569; cursor:pointer; font-weight:500;">Cancel</button>
                <button onclick="executeExport()" style="padding:8px 16px; border-radius:6px; border:none; background:#4285F4; color:white; cursor:pointer; font-weight:500; display:flex; gap:8px; align-items:center;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    Download
                </button>
            </div>
        </div>
    </div>
    <!-- Sidebar -->
    <div class="ai-w-sidebar">
        <div class="ai-w-sidebar-header">
            <h5>AI Dashboard Assistant</h5>
            <button class="btn-gemini-new" onclick="startNewChat()">+ New Chat</button>
        </div>
        <div class="ai-w-session-list" id="aiSessionList">
            <?php foreach ($ai_widget_sessions as $s): ?>
                <div class="ai-w-session" id="sess-<?php echo $s['id']; ?>"
                    onclick="loadSession(<?php echo $s['id']; ?>, this)">
                    <span class="sess-title"
                        ondblclick="editSessionTitle(event, <?php echo $s['id']; ?>, this)"><?php echo htmlspecialchars($s['title']); ?></span>
                    <button class="btn-delete-chat" onclick="deleteSession(event, <?php echo $s['id']; ?>)"
                        title="Delete Chat">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Main -->
    <div class="ai-w-main">
        <div class="ai-w-header">
            <div
                style="font-weight: 500; display: flex; align-items: center; gap: 10px; font-size: 1.1rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 60%;">
                <button class="btn-sidebar-toggle" onclick="toggleAiSidebar()" title="Toggle Sidebar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    style="flex-shrink:0;">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <span id="currentChatTitle"
                    ondblclick="if(currentAiSessionId) editSessionTitle(event, currentAiSessionId, this)"
                    style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; cursor: pointer;"
                    title="Double-click to rename">AI Dashboard Assistant</span>
            </div>
            <div>
                <button class="btn-sidebar-toggle" onclick="toggleFullscreen()" title="Toggle Fullscreen">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path
                            d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3">
                        </path>
                    </svg>
                </button>
            </div>
        </div>

        <div class="ai-w-history" id="aiHistory">
            <div style="text-align:center; margin-top:40px; color:#94a3b8;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"
                    style="margin-bottom:15px; opacity:0.5;">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <br>How can I help you analyze your data today?
            </div>
        </div>

        <div class="ai-w-input-area">
            <div class="ai-w-input-wrap">
                <input type="text" id="aiInput" placeholder="Write a prompt, generate the data...">
                <button id="aiSendBtn" onclick="submitAiPrompt()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
    Chart.register(ChartDataLabels);
    Chart.defaults.color = '#475569';
    Chart.defaults.plugins.datalabels = {
        color: '#ffffff',
        font: { weight: 'bold' },
        textStrokeColor: 'rgba(0,0,0,0.4)',
        textStrokeWidth: 3,
        display: function(context) {
            let value = context.dataset.data[context.dataIndex];
            let v = typeof value === 'object' ? (value.r !== undefined ? value.r : value.y) : value;
            return v > 0; // Only display if greater than 0
        },
        formatter: (value) => {
            return typeof value === 'object' ? (value.r !== undefined ? value.r : value.y) : value;
        }
    };

    function toggleFullscreen() {
        const widget = document.getElementById('aiWidget');
        widget.classList.toggle('ai-w-expanded');
        setTimeout(() => {
            for (let id in Chart.instances) {
                Chart.instances[id].resize();
            }
        }, 300);
    }

    function toggleAiSidebar() {
        const sidebar = document.querySelector('.ai-w-sidebar');
        sidebar.classList.toggle('collapsed');
        // specifically resize charts instead of triggering global window resize
        setTimeout(() => {
            for (let id in Chart.instances) {
                Chart.instances[id].resize();
            }
        }, 300);
    }

    const historyBox = document.getElementById('aiHistory');
    const input = document.getElementById('aiInput');
    const sendBtn = document.getElementById('aiSendBtn');
    
    let promptHistory = [];
    let historyIndex = -1;
    let currentAiSessionId = null;
    let currentAbortController = null;

    const chartColors = [
        'rgba(66, 133, 244, 0.8)',   // Blue
        'rgba(155, 114, 203, 0.8)',  // Purple
        'rgba(217, 101, 112, 0.8)',  // Red
        'rgba(16, 185, 129, 0.8)',   // Green
        'rgba(245, 158, 11, 0.8)',   // Orange
        'rgba(14, 165, 233, 0.8)',   // Light Blue
        'rgba(236, 72, 153, 0.8)',   // Pink
        'rgba(139, 92, 246, 0.8)',   // Violet
        'rgba(20, 184, 166, 0.8)',   // Teal
        'rgba(234, 179, 8, 0.8)',    // Yellow
        'rgba(99, 102, 241, 0.8)',   // Indigo
        'rgba(244, 63, 94, 0.8)',    // Rose
        'rgba(168, 85, 247, 0.8)',   // Fuchsia
        'rgba(132, 204, 22, 0.8)',   // Lime
        'rgba(100, 116, 139, 0.8)',  // Slate
        'rgba(249, 115, 22, 0.8)',   // Bright Orange
        'rgba(6, 182, 212, 0.8)',    // Cyan
        'rgba(217, 70, 239, 0.8)',   // Magenta
        'rgba(34, 197, 94, 0.8)',    // Emerald
        'rgba(113, 113, 122, 0.8)'   // Zinc
    ];

    function scrollChat() {
        historyBox.scrollTop = historyBox.scrollHeight;
    }

    function addRow(role, content, msgId = null) {
        const row = document.createElement('div');
        row.className = `ai-w-row ${role}`;
        let ctxAttr = msgId ? `oncontextmenu="showContextMenu(event, ${msgId}, this)"` : '';
        if (role === 'user') {
            row.innerHTML = `<div class="ai-w-bubble-user" ${ctxAttr}>${content.replace(/</g, "&lt;")}</div>`;
        } else {
            row.innerHTML = `
                <div class="ai-w-bubble-ai" ${ctxAttr}>
                    <div class="ai-w-avatar"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg></div>
                    <div class="ai-w-content">${content}</div>
                </div>`;
        }
        historyBox.appendChild(row);
        scrollChat();
        return row;
    }

    function renderWidgetData(container, rawData, chartType, pChartTitle, pIsStacked) {
        if (!rawData) return;
        try {
            let data = JSON.parse(rawData);
            let chartTitle = pChartTitle || null;
            let isStacked = pIsStacked || false;

            if (data && !Array.isArray(data) && data.data) {
                chartTitle = data.chartTitle || chartTitle;
                isStacked = data.isStacked || isStacked;
                data = data.data;
            }

            if (!data || data.length === 0) return;
            if (data.length === 1 && Object.keys(data[0]).length === 1) return;

            const keys = Object.keys(data[0]);
            let canChart = false;

            let isHorizontal = false;
            if (chartType && chartType.toLowerCase().includes('horizontal')) {
                isHorizontal = true;
            }

            let type = data.length > 15 ? 'line' : (data.length <= 5 ? 'doughnut' : 'bar');
            if (chartType && chartType !== 'auto') {
                type = chartType.toLowerCase();
                if (type === 'horizontalbar') type = 'bar';
                if (!['bar', 'line', 'pie', 'doughnut', 'scatter', 'radar', 'polararea', 'bubble'].includes(type)) type = 'bar';
                if (type === 'polararea') type = 'polarArea';
            }

            let chartDataObj = null;
            let multiCharts = null;

            if (type === 'bubble') {
                canChart = true;
                let numKeys = keys.filter(k => !isNaN(parseFloat(data[0][k])) && isFinite(data[0][k]));
                let labelKey = keys.find(k => !numKeys.includes(k));
                if (numKeys.length >= 3) {
                    let maxR = Math.max(...data.map(row => parseFloat(row[numKeys[2]]) || 0));
                    let maxAllowedRadius = 35; // Maximum pixel size for the largest bubble
                    
                    chartDataObj = {
                        datasets: data.map((row, i) => {
                            let color = chartColors[i % chartColors.length];
                            let rawR = parseFloat(row[numKeys[2]]) || 0;
                            // Scale the radius proportionally, giving a minimum of 4px if it's > 0, and max of 35px
                            let scaledR = maxR > 0 ? (rawR / maxR) * maxAllowedRadius : 5;
                            if (rawR === 0) scaledR = 0; // If it's explicitly 0, it shouldn't show a bubble
                            else if (scaledR < 4) scaledR = 4; // Ensure small non-zero values are still visible

                            return {
                                label: labelKey && row[labelKey] ? row[labelKey] : `Bubble ${i+1}`,
                                data: [{
                                    x: parseFloat(row[numKeys[0]]),
                                    y: parseFloat(row[numKeys[1]]),
                                    r: scaledR
                                }],
                                backgroundColor: color,
                                borderColor: color.replace('0.8', '1'),
                                borderWidth: 1
                            };
                        })
                    };
                } else {
                    canChart = false;
                }
            } else if (keys.length === 3 && isStacked) {
                canChart = true;
                let valueKey = null, xAxisKey = null, groupKey = null;
                for (let k of keys) {
                    if (!isNaN(parseFloat(data[0][k])) && isFinite(data[0][k])) valueKey = k;
                }
                let otherKeys = keys.filter(k => k !== valueKey);
                xAxisKey = otherKeys[0];
                groupKey = otherKeys[1];

                let xAxisLabels = [...new Set(data.map(r => r[xAxisKey]))];
                let groupLabels = [...new Set(data.map(r => r[groupKey]))];

                if (['pie', 'doughnut', 'polarArea'].includes(type)) {
                    multiCharts = groupLabels.map(g => {
                        let groupValues = xAxisLabels.map(x => {
                            let row = data.find(r => r[xAxisKey] === x && r[groupKey] === g);
                            return row ? parseFloat(row[valueKey]) : 0;
                        });
                        return {
                            title: chartTitle ? `${chartTitle} (${g})` : String(g),
                            data: {
                                labels: xAxisLabels,
                                datasets: [{
                                    label: String(g),
                                    data: groupValues,
                                    backgroundColor: chartColors,
                                    borderColor: chartColors.map(c => c.replace('0.8', '1')),
                                    borderWidth: 1
                                }]
                            }
                        };
                    });
                } else {
                    let datasets = groupLabels.map((g, i) => {
                        let groupValues = xAxisLabels.map(x => {
                            let row = data.find(r => r[xAxisKey] === x && r[groupKey] === g);
                            return row ? parseFloat(row[valueKey]) : 0;
                        });
                        let color = chartColors[i % chartColors.length];
                        return {
                            label: String(g),
                            data: groupValues,
                            backgroundColor: color,
                            borderColor: color.replace('0.8', '1'),
                            borderWidth: 1,
                            borderRadius: type === 'bar' ? 6 : 0
                        };
                    });

                    chartDataObj = { labels: xAxisLabels, datasets: datasets };
                }

            } else if (keys.length === 2 || keys.length === 3) {
                let labelKey = null, valueKey = null;
                for (let k of keys) {
                    if (!isNaN(parseFloat(data[0][k])) && isFinite(data[0][k])) valueKey = k;
                    else if (k !== valueKey) labelKey = k;
                }
                if (!labelKey) labelKey = keys[0];
                if (valueKey && labelKey && valueKey !== labelKey) canChart = true;

                if (canChart && data.length > 1) {
                    const labels = data.map(r => r[labelKey]);
                    const values = data.map(r => parseFloat(r[valueKey]));

                    chartDataObj = {
                        labels: labels,
                        datasets: [{
                            label: valueKey, data: values,
                            backgroundColor: chartColors,
                            borderColor: chartColors.map(c => c.replace('0.8', '1')),
                            borderWidth: 1, borderRadius: type === 'bar' ? 6 : 0
                        }]
                    };
                } else {
                    canChart = false;
                }
            }

            if (canChart && multiCharts) {
                let multiHtml = `<div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center;">`;
                let renderConfigs = [];

                multiCharts.forEach((mc, i) => {
                    const chartId = 'wchart_' + Date.now() + i + Math.floor(Math.random() * 1000);
                    multiHtml += `
                        <div style="flex: 1; min-width: 300px; max-width: 45%;">
                            <div class="ai-w-chart">
                                <canvas id="${chartId}" style="background-color: white;"></canvas>
                            </div>
                            <div style="text-align: right; margin-top: 8px;">
                                <div style="position: relative; display: inline-block;">
                                    <button style="background:none; border:none; color:#94a3b8; cursor:pointer; padding:4px; border-radius:50%; transition:background 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'" title="Export Options">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="5" r="1.5"></circle>
                                            <circle cx="12" cy="12" r="1.5"></circle>
                                            <circle cx="12" cy="19" r="1.5"></circle>
                                        </svg>
                                    </button>
                                    <select onchange="if(this.value){ openExportModal('${chartId}', this.value); this.value=''; }" 
                                            style="position:absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer;">
                                        <option value="" disabled selected>Export...</option>
                                        <option value="png_bg">PNG (White Background)</option>
                                        <option value="png_trans">PNG (Transparent)</option>
                                        <option value="pdf">PDF</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    `;
                    renderConfigs.push({ id: chartId, title: mc.title, data: mc.data });
                });
                multiHtml += `</div>`;
                container.innerHTML += multiHtml;

                renderConfigs.forEach(rc => {
                    let options = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } };
                    if (rc.title) {
                        options.plugins.title = { display: true, text: rc.title, font: { size: 14 } };
                    }
                    if (!['pie', 'doughnut'].includes(type)) {
                        options.scales = options.scales || {};
                        options.scales.x = options.scales.x || {};
                        options.scales.x.ticks = {
                            callback: function(value) {
                                let label = this.getLabelForValue(value) || '';
                                return label.length > 25 ? label.substr(0, 25) + '...' : label;
                            }
                        };
                    }
                    new Chart(document.getElementById(rc.id).getContext('2d'), {
                        type: type,
                        data: rc.data,
                        options: options
                    });
                });

            } else if (canChart && chartDataObj) {
                const chartId = 'wchart_' + Date.now() + Math.floor(Math.random() * 1000);
                container.innerHTML += `
                    <div class="ai-w-chart">
                        <canvas id="${chartId}" style="background-color: white;"></canvas>
                    </div>
                    <div style="text-align: right; margin-top: 8px;">
                        <div style="position: relative; display: inline-block;">
                            <button style="background:none; border:none; color:#94a3b8; cursor:pointer; padding:4px; border-radius:50%; transition:background 0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'" title="Export Options">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="5" r="1.5"></circle>
                                    <circle cx="12" cy="12" r="1.5"></circle>
                                    <circle cx="12" cy="19" r="1.5"></circle>
                                </svg>
                            </button>
                            <select onchange="if(this.value){ openExportModal('${chartId}', this.value); this.value=''; }" 
                                    style="position:absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer;">
                                <option value="" disabled selected>Export...</option>
                                <option value="png_bg">PNG (White Background)</option>
                                <option value="png_trans">PNG (Transparent)</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>
                    </div>`;

                let options = {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right' }
                    }
                };

                if (chartDataObj.datasets.length === 1 && !['pie', 'doughnut', 'scatter', 'polarArea', 'radar', 'bubble'].includes(type)) {
                    options.plugins.legend.display = true;
                    options.plugins.legend.labels = {
                        generateLabels: function(chart) {
                            const data = chart.data;
                            if (data.labels.length && data.datasets.length) {
                                return data.labels.map(function(label, i) {
                                    const meta = chart.getDatasetMeta(0);
                                    const style = meta.controller.getStyle(i);
                                    return {
                                        text: label,
                                        fillStyle: style.backgroundColor,
                                        strokeStyle: style.borderColor,
                                        lineWidth: style.borderWidth,
                                        hidden: isNaN(data.datasets[0].data[i]) || meta.data[i].hidden,
                                        index: i
                                    };
                                });
                            }
                            return [];
                        }
                    };
                    options.plugins.legend.onClick = function(e, legendItem, legend) {
                        const index = legendItem.index;
                        const ci = legend.chart;
                        const meta = ci.getDatasetMeta(0);
                        if (meta.data[index]) {
                            meta.data[index].hidden = !meta.data[index].hidden;
                            ci.update();
                        }
                    };
                }

                if (chartTitle) {
                    options.plugins.title = {
                        display: true,
                        text: chartTitle,
                        font: { size: 16 }
                    };
                }

                if (!['pie', 'doughnut', 'polarArea', 'radar'].includes(type)) {
                    options.scales = options.scales || {};
                    let primaryAxis = isHorizontal ? 'y' : 'x';
                    options.scales[primaryAxis] = options.scales[primaryAxis] || {};
                    options.scales[primaryAxis].ticks = {
                        callback: function(value) {
                            let label = this.getLabelForValue(value) || '';
                            return label.length > 25 ? label.substr(0, 25) + '...' : label;
                        }
                    };
                }

                if (isStacked && type === 'bar') {
                    options.scales.x = options.scales.x || {};
                    options.scales.y = options.scales.y || {};
                    options.scales.x.stacked = true;
                    options.scales.y.stacked = true;
                }

                if (isHorizontal && type === 'bar') {
                    options.indexAxis = 'y';
                }

                if (type === 'scatter') {
                    // Convert datasets data to {x, y} format for Chart.js scatter plot
                    chartDataObj.datasets.forEach(ds => {
                        let scatterData = [];
                        for(let i=0; i<chartDataObj.labels.length; i++) {
                            let xVal = chartDataObj.labels[i];
                            // Parse as float if possible for a true linear scatter, otherwise keep as categorical string
                            if (!isNaN(parseFloat(xVal)) && isFinite(xVal)) {
                                xVal = parseFloat(xVal);
                            }
                            scatterData.push({
                                x: xVal,
                                y: ds.data[i]
                            });
                        }
                        ds.data = scatterData;
                        ds.showLine = false; // Never draw lines between points in a scatter
                        ds.label = (chartDataObj.labels && chartDataObj.labels.length > 0 && data.length > 0 ? Object.keys(data[0]).find(k => chartDataObj.labels[0] == data[0][k]) || 'X' : 'X') + ' vs ' + ds.label;
                    });
                    
                    // If the first label is a string, force x-axis to be categorical
                    if (chartDataObj.labels && chartDataObj.labels.length > 0 && typeof chartDataObj.labels[0] === 'string' && isNaN(parseFloat(chartDataObj.labels[0]))) {
                        options.scales = options.scales || {};
                        options.scales.x = options.scales.x || {};
                        options.scales.x.type = 'category';
                        options.scales.x.labels = chartDataObj.labels;
                    } else {
                        // Force linear numeric X axis
                        options.scales = options.scales || {};
                        options.scales.x = options.scales.x || {};
                        options.scales.x.type = 'linear';
                        delete chartDataObj.labels;
                    }
                }

                new Chart(document.getElementById(chartId).getContext('2d'), {
                    type: type,
                    data: chartDataObj,
                    options: options
                });
            } else {
                let html = `<table class="ai-w-table"><thead><tr>`;
                keys.forEach(k => html += `<th>${k}</th>`);
                html += `</tr></thead><tbody>`;
                data.forEach(row => {
                    html += `<tr>`;
                    keys.forEach(k => html += `<td>${row[k]}</td>`);
                    html += `</tr>`;
                });
                html += `</tbody></table>`;
                container.innerHTML += html;
            }
        } catch (e) { console.error(e); }
    }



    let currentExportChartId = null;
    let currentExportFormat = null;
    let previewChartInstance = null;

    function openExportModal(chartId, format) {
        currentExportChartId = chartId;
        currentExportFormat = format;
        
        document.getElementById('chartExportModal').style.display = 'flex';
        updatePreviewChart();
    }

    function closeExportModal() {
        document.getElementById('chartExportModal').style.display = 'none';
        if (previewChartInstance) {
            previewChartInstance.destroy();
            previewChartInstance = null;
        }
        currentExportChartId = null;
        currentExportFormat = null;
    }

    function updatePreviewChart() {
        if (!currentExportChartId) return;

        const originalChart = Chart.getChart(currentExportChartId);
        if (!originalChart) return;

        const width = parseInt(document.getElementById('exportWidth').value) || 1200;
        const height = parseInt(document.getElementById('exportHeight').value) || 800;

        const container = document.getElementById('exportCanvasContainer');
        container.style.width = width + 'px';
        container.style.height = height + 'px';
        
        const box = document.querySelector('.export-modal-preview-box');
        const scaleX = (box.clientWidth - 40) / width;
        const scaleY = (box.clientHeight - 40) / height;
        const scale = Math.min(scaleX, scaleY, 1);
        container.style.transform = `scale(${scale})`;

        if (previewChartInstance) {
            previewChartInstance.destroy();
        }

        const canvas = document.getElementById('exportPreviewCanvas');
        canvas.width = width;
        canvas.height = height;

        const originalConfig = originalChart.config;
        
        let newOptions = {
            ...originalConfig.options,
            animation: false,
            responsive: true,
            maintainAspectRatio: false
        };

        previewChartInstance = new Chart(canvas.getContext('2d'), {
            type: originalConfig.type,
            data: originalConfig.data,
            options: newOptions
        });
    }

    function executeExport() {
        if (!previewChartInstance) return;
        
        const canvas = document.getElementById('exportPreviewCanvas');
        const format = currentExportFormat;
        let url = canvas.toDataURL('image/png', 1.0);

        if (format === 'png_bg' || format === 'pdf') {
            const ctx = canvas.getContext('2d');
            ctx.save();
            ctx.globalCompositeOperation = 'destination-over';
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            url = canvas.toDataURL('image/png', 1.0);
            ctx.restore();
        }

        if (format === 'pdf') {
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF({
                orientation: canvas.width > canvas.height ? 'landscape' : 'portrait',
                unit: 'px',
                format: [canvas.width, canvas.height]
            });
            pdf.addImage(url, 'PNG', 0, 0, canvas.width, canvas.height);
            pdf.save('chart_export.pdf');
        } else {
            const link = document.createElement('a');
            link.download = 'chart_export.png';
            link.href = url;
            link.click();
        }
        
        closeExportModal();
    }

    async function loadSession(id, element) {
        currentAiSessionId = id;
        document.querySelectorAll('.ai-w-session').forEach(el => el.classList.remove('active'));

        let sessionTitle = 'AI Dashboard Assistant';

        if (element) {
            element.classList.add('active');
            sessionTitle = element.querySelector('.sess-title').innerText;
        } else {
            const row = document.getElementById('sess-' + id);
            if (row) {
                row.classList.add('active');
                sessionTitle = row.querySelector('.sess-title').innerText;
            }
        }

        document.getElementById('currentChatTitle').innerText = sessionTitle;

        historyBox.innerHTML = '<div style="text-align:center; margin-top:40px; color:#94a3b8;">Loading...</div>';

        try {
            const res = await fetch(`${window.BASE_URL}/admin/api/get_chat.php?session_id=${id}`);
            const data = await res.json();
            historyBox.innerHTML = '';

            if (data.messages) {
                data.messages.forEach(msg => {
                    if (msg.role === 'user') addRow('user', msg.content, msg.id);
                    else {
                        const row = addRow('ai', msg.content, msg.id);
                        let rd = msg.raw_data_json;
                        let rChartTitle = null, rIsStacked = false;
                        try {
                            let parsedRd = JSON.parse(rd);
                            if (parsedRd && !Array.isArray(parsedRd) && parsedRd.data) {
                                rChartTitle = parsedRd.chartTitle;
                                rIsStacked = parsedRd.isStacked;
                            }
                        } catch (e) { }
                        renderWidgetData(row.querySelector('.ai-w-content'), rd, msg.chart_type, rChartTitle, rIsStacked);
                    }
                });
            }
        } catch (e) {
            historyBox.innerHTML = '<div style="text-align:center; color:red;">Failed to load session</div>';
        }
    }

    function startNewChat() {
        currentAiSessionId = null;
        document.querySelectorAll('.ai-w-session').forEach(el => el.classList.remove('active'));
        document.getElementById('currentChatTitle').innerText = 'AI Dashboard Assistant';
        historyBox.innerHTML = '<div style="text-align:center; margin-top:40px; color:#94a3b8;"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom:15px; opacity:0.5;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg><br>How can I help you analyze your data today?</div>';
    }

    async function deleteSession(event, id) {
        event.stopPropagation(); // prevent clicking the session
        if (!confirm("Delete this chat?")) return;

        try {
            const res = await fetch(window.BASE_URL + '/admin/api/delete_chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ session_id: id })
            });
            const result = await res.json();
            if (result.success) {
                document.getElementById('sess-' + id).remove();
                if (currentAiSessionId === id) {
                    startNewChat();
                }
            } else {
                alert("Failed to delete: " + result.error);
            }
        } catch (e) {
            alert("Network error deleting chat.");
        }
    }

    function editSessionTitle(event, sessionId, spanElement) {
        event.stopPropagation();
        if (spanElement.querySelector('input')) return; // Already editing

        const oldTitle = spanElement.innerText;
        const input = document.createElement('input');
        input.type = 'text';
        input.value = oldTitle;
        input.style.width = '100%';
        input.style.background = 'transparent';
        input.style.border = '1px solid #4285F4';
        input.style.color = 'inherit';
        input.style.outline = 'none';
        input.style.fontFamily = 'inherit';
        input.style.fontSize = 'inherit';

        spanElement.innerHTML = '';
        spanElement.appendChild(input);
        input.focus();

        async function saveTitle() {
            const newTitle = input.value.trim() || oldTitle;
            spanElement.innerHTML = newTitle; // Revert immediately for UX

            if (newTitle !== oldTitle) {
                try {
                    await fetch(window.BASE_URL + '/admin/api/rename_chat.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ session_id: sessionId, title: newTitle })
                    });

                    // Sync the other title if it's the active one
                    if (spanElement.id === 'currentChatTitle') {
                        const sideSpan = document.querySelector('#sess-' + sessionId + ' .sess-title');
                        if (sideSpan) sideSpan.innerText = newTitle;
                    } else {
                        if (currentAiSessionId === sessionId) {
                            document.getElementById('currentChatTitle').innerText = newTitle;
                        }
                    }
                } catch (e) {
                    console.error("Failed to save title", e);
                }
            }
        }

        input.addEventListener('blur', saveTitle);
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                input.blur();
            }
        });
    }

    async function submitAiPrompt() {
        if (currentAbortController) {
            currentAbortController.abort();
            return;
        }

        const prompt = input.value.trim();
        if (!prompt) return;
        
        // Add to prompt history
        if (promptHistory.length === 0 || promptHistory[promptHistory.length - 1] !== prompt) {
            promptHistory.push(prompt);
        }
        historyIndex = promptHistory.length;
        
        input.value = '';

        if (historyBox.innerHTML.includes('How can I help')) historyBox.innerHTML = '';
        const userRow = addRow('user', prompt);

        const loadingId = 'load_' + Date.now();
        const loadingRow = addRow('ai', '<span style="color:#94a3b8;">Thinking...</span>');
        loadingRow.id = loadingId;
        loadingRow.querySelector('.ai-w-avatar').classList.add('thinking');

        currentAbortController = new AbortController();
        sendBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="6" width="12" height="12"></rect></svg>';
        sendBtn.style.background = '#dc2626';

        try {
            const res = await fetch(window.BASE_URL + '/admin/ai_query.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ prompt: prompt, session_id: currentAiSessionId, model: 'auto' }),
                signal: currentAbortController.signal
            });
            const result = await res.json();

            loadingRow.querySelector('.ai-w-avatar').classList.remove('thinking');
            const contentDiv = loadingRow.querySelector('.ai-w-content');
            contentDiv.innerHTML = result.error || result.unsupported || result.message;
            if (result.error || result.unsupported) {
                contentDiv.style.color = '#dc2626';
            } else if (result.data) {
                if (result.user_msg_id) {
                    userRow.querySelector('.ai-w-bubble-user').setAttribute('oncontextmenu', `showContextMenu(event, ${result.user_msg_id}, this)`);
                }
                if (result.ai_msg_id) {
                    loadingRow.querySelector('.ai-w-bubble-ai').setAttribute('oncontextmenu', `showContextMenu(event, ${result.ai_msg_id}, this)`);
                }
                renderWidgetData(contentDiv, JSON.stringify(result.data), result.chartType, result.chartTitle, result.isStacked);
                if (result.session_id && !currentAiSessionId) {
                    currentAiSessionId = result.session_id;
                    const titleStr = prompt.substring(0, 47) + (prompt.length > 47 ? '...' : '');
                    document.getElementById('currentChatTitle').innerText = titleStr;

                    const slist = document.getElementById('aiSessionList');
                    const newItem = document.createElement('div');
                    newItem.className = 'ai-w-session active';
                    newItem.id = 'sess-' + result.session_id;
                    newItem.onclick = function () { loadSession(result.session_id, this); };
                    newItem.oncontextmenu = function(e) { e.preventDefault(); deleteSession(e, result.session_id); };

                    newItem.innerHTML = `
                        <span class="sess-title">${prompt.substring(0, 47) + (prompt.length > 47 ? '...' : '')}</span>
                        <button class="btn-delete-chat" onclick="deleteSession(event, ${result.session_id})" title="Delete Chat">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        </button>
                    `;
                    slist.insertBefore(newItem, slist.firstChild);
                }
            }
        } catch (e) {
            loadingRow.querySelector('.ai-w-avatar').classList.remove('thinking');
            const contentDiv = loadingRow.querySelector('.ai-w-content');
            if (e.name === 'AbortError') {
                contentDiv.innerHTML = "<em>Prompt stopped.</em>";
                contentDiv.style.color = '#94a3b8';
            } else {
                contentDiv.innerHTML = "Error communicating with server.";
                contentDiv.style.color = '#dc2626';
            }
        } finally {
            currentAbortController = null;
            sendBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>';
            sendBtn.style.background = '#4285F4';
            input.focus();
            scrollChat();
        }
    }

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            submitAiPrompt();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (historyIndex > 0) {
                historyIndex--;
                input.value = promptHistory[historyIndex];
            }
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (historyIndex < promptHistory.length - 1) {
                historyIndex++;
                input.value = promptHistory[historyIndex];
            } else {
                historyIndex = promptHistory.length;
                input.value = '';
            }
        }
    });

    let currentContextMenuMsgId = null;
    let currentContextMenuElement = null;

    function showContextMenu(e, msgId, element) {
        e.preventDefault();
        e.stopPropagation();
        currentContextMenuMsgId = msgId;
        currentContextMenuElement = element.closest('.ai-w-row');
        
        const menu = document.getElementById('chatContextMenu');
        menu.style.display = 'block';
        
        let x = e.clientX;
        let y = e.clientY;
        
        if (x + 150 > window.innerWidth) x -= 150;
        if (y + 50 > window.innerHeight) y -= 50;
        
        menu.style.left = x + 'px';
        menu.style.top = y + 'px';
    }

    document.addEventListener('click', function() {
        const menu = document.getElementById('chatContextMenu');
        if (menu) menu.style.display = 'none';
    });

    async function confirmDeleteMessage() {
        if (!currentContextMenuMsgId || !currentContextMenuElement) return;
        
        const menu = document.getElementById('chatContextMenu');
        if (menu) menu.style.display = 'none';

        if (confirm("Delete this message?")) {
            try {
                const res = await fetch(window.BASE_URL + '/admin/api/delete_message.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ message_id: currentContextMenuMsgId })
                });
                const result = await res.json();
                if (result.success) {
                    currentContextMenuElement.style.transition = 'opacity 0.3s, transform 0.3s';
                    currentContextMenuElement.style.opacity = '0';
                    currentContextMenuElement.style.transform = 'scale(0.9)';
                    setTimeout(() => {
                        currentContextMenuElement.remove();
                    }, 300);
                } else {
                    alert("Failed to delete message: " + result.error);
                }
            } catch(e) {
                alert("Network error deleting message.");
            }
        }
    }
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>