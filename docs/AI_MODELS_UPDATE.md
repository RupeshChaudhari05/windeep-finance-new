# AI Models Update - DeepSeek Low Price & OpenRouter Integration

## Summary
Added support for **DeepSeek ultra-cheap models** and **OpenRouter multi-model aggregation** to the AI Assistant. Users can now access:
- DeepSeek Chat (V3) at ultra-low prices (₹1.19/1M tokens)
- OpenRouter aggregated models (DeepSeek, Llama, Qwen, Gemini, GPT-4.5)
- Updated pricing for accurate cost estimation

## Changes Made

### 1. **Ai_client.php** (Library)
- Added OpenRouter provider support to class documentation
- Updated `configure()` method to handle OpenRouter base URL (`https://openrouter.ai/api/v1`)
- Extended JSON response format support to include OpenRouter (`['openai', 'deepseek', 'openrouter']`)

### 2. **Ai_assistant.php** (Controller)
- **Updated MODEL_CATALOG** with:
  - **DeepSeek models** with corrected ultra-low pricing:
    - `deepseek-chat`: ₹1.19-2.38/M tokens (was ₹23.8-35.7)
    - `deepseek-reasoner`: ₹46.75-186/M tokens
  
  - **OpenRouter provider** with 6 cost-effective models:
    - `deepseek/deepseek-r1-distill-llama-70b`: ₹5.95-11.9/M (cheapest with reasoning)
    - `deepseek/deepseek-chat`: ₹1.70-3.4/M (ultra cheap, very fast)
    - `qwen/qwen-2.5-7b-instruct`: ₹5.95-11.9/M (good for Indic languages)
    - `llama/llama-3.3-70b-instruct`: ₹31.45-46.75/M (strong multi-lingual)
    - `openai/gpt-4.5-turbo-2025-01-13`: ₹35.7-142.8/M (latest via aggregator)
    - `google/gemini-2.0-flash-001`: ₹4.25-17/M (cheap, multimodal)

- **Updated `save_settings()` method**:
  - Added 'openrouter' to valid provider list
  - Added `ai_openrouter_model` and `ai_openrouter_key` to settings
  - Extended key list to include `ai_openrouter_key`

- **Updated `_config()` method**:
  - Added `openrouter_model` configuration (default: `deepseek/deepseek-chat`)
  - Added `openrouter_key` configuration
  - Updated public config to include `openrouter_key_set` flag

- **Updated `_client()` method**:
  - Added OpenRouter provider mapping
  - Extended field mapping to include OpenRouter model and key
  - Added `case 'openrouter':` handler to configure OpenRouter client

### 3. **index.php** (View)
- **Updated Provider dropdown**:
  - Added OpenRouter option: "OpenRouter — Cheapest (DeepSeek, Llama, Qwen)"
  - Updated DeepSeek label: "DeepSeek — Very cheap"
  - Organized providers by cost tier

- **Added OpenRouter Configuration Section**:
  - Model selector dropdown bound to catalog
  - Password field for API key (masked)
  - Help text with OpenRouter dashboard link
  - Emphasis on cost efficiency

## Pricing Reference (USD per 1M tokens)

### Direct Providers
| Model | Input | Output | INR (₹) |
|-------|-------|--------|---------|
| DeepSeek Chat (V3) | $0.014 | $0.028 | ₹1.19-2.38 |
| Qwen 2.5 (7B) OpenRouter | $0.07 | $0.14 | ₹5.95-11.9 |
| DeepSeek Chat OpenRouter | $0.02 | $0.04 | ₹1.70-3.4 |
| Gemini 2.0 Flash OpenRouter | $0.05 | $0.20 | ₹4.25-17 |
| Llama 3.3 (70B) OpenRouter | $0.37 | $0.55 | ₹31.45-46.75 |
| GPT-4o-mini | $0.15 | $0.60 | ₹12.75-51 |
| GPT-4.5 Turbo OpenRouter | $0.42 | $1.68 | ₹35.7-142.8 |

**Note:** Prices use USD_INR rate of 85. Confirm latest rates on provider websites.

## Configuration Instructions

### For DeepSeek API
1. Go to **AI Settings** → **Integration** tab
2. Select provider: **"DeepSeek — Very cheap"**
3. Model: Select **"DeepSeek Chat (V3)"**
4. Get API key from: https://platform.deepseek.com
5. Enter key and save

### For OpenRouter API
1. Go to **AI Settings** → **Integration** tab
2. Select provider: **"OpenRouter — Cheapest"**
3. Model: Select any OpenRouter model (recommended: DeepSeek Chat)
4. Get API key from: https://openrouter.ai → Dashboard → API keys
5. Enter key and save

## Environment Variables (.env)
Optional - for defaults and override:
```
AI_PROVIDER=openrouter
AI_OPENROUTER_MODEL=deepseek/deepseek-chat
AI_OPENROUTER_KEY=sk-or-xxx
AI_DEEPSEEK_MODEL=deepseek-chat
AI_DEEPSEEK_KEY=sk-xxx
AI_MONTHLY_BUDGET_INR=4000
AI_USD_INR=85
```

## Backward Compatibility
✅ All existing configurations remain unchanged. Switching providers does not affect stored conversation history or audit logs.

## Testing Checklist
- [ ] OpenRouter provider loads in settings UI
- [ ] All OpenRouter models appear in dropdown
- [ ] API key can be entered and saved
- [ ] Test connection works for OpenRouter
- [ ] Chat can be sent using OpenRouter models
- [ ] Model pricing displays correctly in UI
- [ ] Marathi language support works with new models

## Next Steps
1. Set up OpenRouter account and get API key
2. Go to AI Assistant settings
3. Select OpenRouter provider
4. Choose a model (start with DeepSeek Chat)
5. Test connection
6. Start using!

## Support
- OpenRouter docs: https://openrouter.ai/docs
- DeepSeek API: https://platform.deepseek.com
- Model list: https://openrouter.ai/models
