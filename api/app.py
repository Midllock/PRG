import os
import requests
from flask import Flask, request, jsonify
from flask_cors import CORS

app = Flask(__name__)

# Povolíme CORS
CORS(app)

# Tvůj API klíč (v produkci je lepší ho nastavit jako Environment Variable v Renderu)
GEMINI_API_KEY = os.environ.get("GEMINI_API_KEY", "AQ.Ab8RN6JUNspU_sLaiu0g7mSV15naEb1hO0byFxSLKVdPwXUgBw")

@app.get("/")
def home():
    return "VDF Support Backend is running"

@app.post("/chat")
def chat():
    data = request.get_json(silent=True) or {}
    message = (data.get("message") or "").strip()

    if not message:
        return jsonify({"error": "message is required"}), 400

    # Připojení k Gemini API (ekvivalent tvého test.php)
    endpoint = f"https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent?key={GEMINI_API_KEY}"
    
    payload = {
        "contents": [
            {
                "role": "user",
                "parts": [{"text": message}]
            }
        ]
    }

    try:
        # Odeslání POST požadavku
        response = requests.post(
            endpoint, 
            json=payload, 
            headers={"Content-Type": "application/json"}
        )
        response.raise_for_status() # Zkontroluje, zda není HTTP chyba (např. 400, 500)
        
        # Zpracování JSON odpovědi od Googlu
        gemini_data = response.json()
        
        # Extrakce samotného textu z komplikované struktury odpovědi
        reply_text = gemini_data["candidates"][0]["content"]["parts"][0]["text"]
        
        return jsonify({"reply": reply_text})

    except Exception as e:
        # Pokud něco selže (špatný klíč, výpadek API), vrátíme chybovou hlášku
        return jsonify({"error": f"Chyba při komunikaci s AI: {str(e)}"}), 500

if __name__ == "__main__":
    port = int(os.environ.get("PORT", "8000"))
    app.run(host="0.0.0.0", port=port, debug=True)