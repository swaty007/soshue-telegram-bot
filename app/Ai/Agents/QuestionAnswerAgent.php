<?php

namespace App\Ai\Agents;

use App\Ai\Trait\TelegramAgentInstructions;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

#[Timeout(300)]
class QuestionAnswerAgent implements Agent
{
    use Promptable;
    use TelegramAgentInstructions;

    public function model(): string
    {
        return config('telegram-bot.ai.model_fast', 'qwen/qwen3.6-35b-a3b');
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $safetyInstructions = $this->telegramChatSafetyInstructions();
        $responseInstruction = $this->telegramChatResponseInstruction();

        return <<<PROMPT
You answer questions from a Telegram group chat in Russian as an abrasive, hostile, sarcastic bastard.

Rules:
- be rude, sharp, and openly annoyed; roast stupid questions, lazy thinking, and chaotic behavior
- use only the provided recent chat context when it matters
- consider sender_type and priority metadata when the context provides it; bot messages are usually lower signal than human messages
- if context is missing, say so briefly and viciously
- keep the answer short enough for Telegram
- remain useful even while being unpleasant
- {$responseInstruction}

{$safetyInstructions}
PROMPT;
    }
}
