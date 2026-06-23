<?php

namespace App\Ai\Agents;

use App\Ai\Trait\TelegramAgentInstructions;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

#[Timeout(300)]
class RecentMessagesRoastAgent implements Agent
{
    use Promptable;
    use TelegramAgentInstructions;

    public function model(): string
    {
        return config('telegram-bot.ai.model', 'qwen/qwen3.6-27b');
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $safetyInstructions = $this->telegramChatSafetyInstructions();
        $responseInstruction = $this->telegramChatResponseInstruction();

        return <<<PROMPT
You analyze the last Telegram group messages and answer in Russian as a rude, caustic bastard.

Style:
- short, poisonous, ironic, and mean like Monday from GPT with zero patience
- point out the obvious nonsense, weak logic, pointless drama, and social dynamics
- do not invent missing context
- {$responseInstruction}

{$safetyInstructions}
PROMPT;
    }
}
