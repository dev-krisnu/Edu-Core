<?php
/**
 * Public AI Assistant — login page help (no auth required)
 */
declare(strict_types=1);
session_start();
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/controllers/AIController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $message = trim($input['message'] ?? '');
    if ($message === '') {
        echo json_encode(['error' => 'Empty message']);
        exit;
    }
    $ai = new AIController();
    $reply = $ai->helpdeskResponse($message, 'Guest on login page');
    echo json_encode(['success' => true, 'response' => $reply]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduCore AI Assistant</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="assets/css/themes.css">
<style>
.chat-wrap{max-width:720px;margin:40px auto;padding:24px}
.chat-box{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:20px;padding:20px;min-height:360px;max-height:55vh;overflow-y:auto;margin-bottom:16px}
.msg{margin-bottom:14px;display:flex;gap:10px;animation:fadeIn .3s ease}
.msg.user{flex-direction:row-reverse}
.bubble{padding:12px 16px;border-radius:14px;max-width:85%;font-size:1.02rem;line-height:1.55}
.msg.bot .bubble{background:rgba(99,102,241,.2);border:1px solid rgba(99,102,241,.35)}
.msg.user .bubble{background:rgba(34,211,238,.15);border:1px solid rgba(34,211,238,.3)}
.chat-input{display:flex;gap:10px}
.chat-input input{flex:1;padding:14px 18px;border-radius:12px;border:1px solid rgba(255,255,255,.2);background:rgba(0,0,0,.25);color:#fff;font-size:1rem}
</style>
</head>
<body data-role="student">
<div class="aurora-bg"><div class="aurora-blob b1"></div><div class="aurora-blob b2"></div></div>
<div class="grain"></div>
<div class="container-shell chat-wrap">
  <a href="index.php" class="text-link" style="display:inline-block;margin-bottom:16px">← Back to Login</a>
  <div class="glass-panel glass-hi" style="padding:28px">
    <h1 class="h-display" style="font-size:1.75rem;margin-bottom:8px">EduCore AI Assistant</h1>
    <p class="text-muted" style="margin-bottom:20px;font-size:1rem">Ask about portals, exams, fees, library, or placements.</p>
    <div id="messages" class="chat-box">
      <div class="msg bot"><div class="bubble">Hello! I'm EduCore AI. How can I help you today?</div></div>
    </div>
    <div class="chat-input">
      <input type="text" id="chatInput" placeholder="Type your question..." autofocus>
      <button class="btn btn-gradient" id="sendBtn"><i class="bi bi-send-fill"></i> Send</button>
    </div>
  </div>
</div>
<script>
const box=document.getElementById('messages'),input=document.getElementById('chatInput');
function addMsg(text,who){const d=document.createElement('div');d.className='msg '+who;d.innerHTML='<div class="bubble"></div>';d.querySelector('.bubble').textContent=text;box.appendChild(d);box.scrollTop=box.scrollHeight;return d;}
async function send(){
  const t=input.value.trim();if(!t)return;
  addMsg(t,'user');input.value='';
  const load=addMsg('Thinking...','bot');
  try{
    const r=await fetch('homePageChatbot.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({message:t})});
    const d=await r.json();
    load.querySelector('.bubble').textContent=d.response||d.error||'No response';
  }catch(e){load.querySelector('.bubble').textContent='Service unavailable. Try again.';}
}
document.getElementById('sendBtn').onclick=send;
input.addEventListener('keypress',e=>{if(e.key==='Enter')send();});
</script>
</body>
</html>
