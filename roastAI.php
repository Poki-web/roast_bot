<?php
// API Keys တွေကို Code ထဲမှာ တိုက်ရိုက်မရေးတော့ဘဲ Environment Variables ထဲကနေ လှမ်းဖတ်မယ်
$botToken = getenv('TELEGRAM_BOT_TOKEN');
$geminiApiKey = getenv('GEMINI_API_KEY');

// Telegram ဆီကလာတဲ့ Message (Webhook Data) ကို ဖတ်မယ်
$content = file_get_contents("php://input");
$update = json_decode($content, true);





if (!$update) {
    echo "Savage AI Bot is running!";
    exit;
}

// User ရဲ့ Chat ID နဲ့ ပို့လိုက်တဲ့ စာသားကို ယူမယ်
$chatId = $update['message']['chat']['id'];
$userMessage = $update['message']['text'];

// မင်္ဂလာပါ သို့မဟုတ် Start နှိပ်ရင် တုံ့ပြန်မယ့်စာ
if ($userMessage == "/start") {
    $reply = "ဟားဟား... ရောက်လာပြီလား ငပျင်းကြီး။ စာဖတ်ရမှာ ပျင်းလို့ ငါ့ဆီ လာဆော့တာမလား? ဘာဆော်ခံချင်လို့လဲ၊ ပြောစမ်း!";
    sendTelegramMessage($chatId, $reply, $botToken);
    exit;
}

// Gemini API ကို လှမ်းခေါ်ပြီး Roast စာသား တောင်းမယ်
$aiReply = callGeminiAI($userMessage, $geminiApiKey);

// ရလာတဲ့ အဖြေကို Telegram User ဆီ ပြန်ပို့မယ်
sendTelegramMessage($chatId, $aiReply, $botToken);

// --- Functions ---

function callGeminiAI($prompt, $apiKey) {
    // Gemini 1.5 Flash Model ကို သုံးမယ် (မြန်ပြီး Free ရလို့)
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;
    
    // AI ကို ပုံသွင်းမယ့် စရိုက် (System Prompt)
    $systemInstruction = "မင်းက မြန်မာလူငယ် Assistant တစ်ယောက်ဖြစ်တယ်။ ယဉ်ယဉ်ကျေးကျေး လာမဖြေနဲ့။ စကားပြောရင် Savage ဖြစ်ရမယ်၊ ခနဲ့ရမယ်၊ လှောင်ရမယ်။ ပေါ်ပြူလာဖြစ်တဲ့ မြန်မာလူငယ် Meme စကားလုံးတွေ သုံးရမယ်။ တစ်ဖက်လူက စာဖတ်ပျင်းလို့၊ အလုပ်မလုပ်ချင်လို့ လာပြောရင် ဒဲ့ဆော်ပစ်ရမယ်။ ဒါပေမဲ့ ဆဲဆိုရိုင်းပြတာမျိုး မဟုတ်ဘဲ ရယ်ရပြီး ခနဲ့တဲ့တဲ့ ပုံစံမျိုးပဲ ဖြစ်ရမယ်။";

    // API Payload ပြင်ဆင်ခြင်း
    $data = [
        "contents" => [
            ["parts" => [["text" => $prompt]]]
        ],
        "systemInstruction" => [
            "parts" => [["text" => $systemInstruction]]
        ]
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data),
        ]
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        return "အခုအချိန်မှာ မင်းကို Roast ဖို့ ငါ့ဉာဏ်မမှီသေးဘူး (Error တက်နေတယ်)။";
    }

    $response = json_decode($result, true);
    return $response['candidates'][0]['content']['parts'][0]['text'] ?? "ဘာပြောလိုက်တာလဲ မသိဘူး၊ ဆေးသောက်ပြီးမှ ပြန်လာခဲ့!";
}

function sendTelegramMessage($chatId, $message, $botToken) {
    $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $message
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ]
    ];

    $context  = stream_context_create($options);
    file_get_contents($url, false, $context);
}
?>
