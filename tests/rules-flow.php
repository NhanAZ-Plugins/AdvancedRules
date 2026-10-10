<?php

declare(strict_types=1);

namespace pocketmine\command {
    class CommandSender {
        /** @var list<string> */
        public array $messages = [];

        public function sendMessage(string $message): void {
            $this->messages[] = $message;
        }
    }

    class Command {
        public function __construct(private string $name) {}

        public function getName(): string {
            return $this->name;
        }
    }
}

namespace pocketmine\player {
    use dktapps\pmforms\CustomForm;
    use pocketmine\command\CommandSender;

    class Player extends CommandSender {
        /** @var list<CustomForm> */
        public array $forms = [];
        public int $kicks = 0;
        public bool $allowKick = true;

        public function __construct(private string $name) {}

        public function getName(): string {
            return $this->name;
        }

        public function sendForm(CustomForm $form): void {
            $this->forms[] = $form;
        }

        public function kick(string $message): bool {
            $this->kicks++;
            return $this->allowKick;
        }
    }
}

namespace pocketmine\event {
    interface Listener {}
}

namespace pocketmine\event\player {
    use pocketmine\player\Player;

    class PlayerJoinEvent {
        public function __construct(private Player $player) {}

        public function getPlayer(): Player {
            return $this->player;
        }
    }
}

namespace pocketmine\utils {
    class TextFormat {
        public const RED = '';
    }
}

namespace pocketmine\plugin {
    use pocketmine\event\Listener;

    class Config {
        /** @param array<string, mixed> $values */
        public function __construct(public array $values) {}

        public function get(string $key, mixed $default = null): mixed {
            return $this->values[$key] ?? $default;
        }
    }

    class Logger {
        /** @var list<string> */
        public array $errors = [];

        public function error(string $message): void {
            $this->errors[] = $message;
        }
    }

    class PluginManager {
        public function registerEvents(Listener $listener, PluginBase $plugin): void {}
    }

    class Server {
        public function getPluginManager(): PluginManager {
            return new PluginManager();
        }
    }

    class PluginBase {
        private Logger $logger;

        public function __construct(private Config $config, private string $folder) {
            $this->logger = new Logger();
        }

        public function getConfig(): Config {
            return $this->config;
        }

        public function getDataFolder(): string {
            return $this->folder;
        }

        public function getServer(): Server {
            return new Server();
        }

        public function getLogger(): Logger {
            return $this->logger;
        }

        protected function saveDefaultConfig(): void {}
    }
}

namespace dktapps\pmforms\element {
    class Label {
        public function __construct(public string $name, public string $text) {}
    }

    class Toggle {
        public function __construct(public string $name, public string $text, public bool $default) {}
    }
}

namespace dktapps\pmforms {
    use Closure;
    use pocketmine\player\Player;

    class CustomFormResponse {
        public function __construct(private bool $agreed) {}

        public function getBool(string $name): bool {
            return $this->agreed;
        }
    }

    class CustomForm {
        /** @param list<object> $elements */
        public function __construct(
            public string $title,
            public array $elements,
            private Closure $onSubmit,
            private ?Closure $onClose = null
        ) {}

        public function submit(Player $player, bool $agreed): void {
            ($this->onSubmit)($player, new CustomFormResponse($agreed));
        }

        public function close(Player $player): void {
            if ($this->onClose !== null) {
                ($this->onClose)($player);
            }
        }
    }
}

namespace {
    use dktapps\pmforms\element\Label;
    use dktapps\pmforms\element\Toggle;
    use NhanAZ\AdvancedRules\Main;
    use pocketmine\command\Command;
    use pocketmine\event\player\PlayerJoinEvent;
    use pocketmine\player\Player;
    use pocketmine\plugin\Config;

    require dirname(__DIR__) . '/src/NhanAZ/AdvancedRules/Main.php';

    function check(bool $condition, string $name): void {
        if (!$condition) {
            throw new RuntimeException("FAIL: $name");
        }
    }

    $folder = sys_get_temp_dir() . '/advancedrules-flow-' . bin2hex(random_bytes(5)) . '/';
    mkdir($folder);
    $config = new Config([
        'TitleForm' => 'Server rules',
        'Rules' => 'Be kind',
        'AgreeButon' => 'I agree',
        'AgreedMessage' => 'Accepted',
        'KickMode' => true,
        'KickMessage' => 'Accept the rules',
    ]);
    $plugin = new Main($config, $folder);
    (new ReflectionMethod(Main::class, 'onEnable'))->invoke($plugin);

    $accepted = new Player('AcceptedPlayer');
    $plugin->onJoin(new PlayerJoinEvent($accepted));
    check(count($accepted->forms) === 1 && !is_file($folder . 'data/AcceptedPlayer'), 'first join prompts');
    $accepted->forms[0]->submit($accepted, true);
    check(is_file($folder . 'data/AcceptedPlayer') && $accepted->messages === ['Accepted'], 'agreement persists');
    $plugin->onJoin(new PlayerJoinEvent($accepted));
    check(count($accepted->forms) === 1 && $accepted->kicks === 0, 'accepted player is not prompted again');

    $declined = new Player('DeclinedPlayer');
    $plugin->onJoin(new PlayerJoinEvent($declined));
    $declined->forms[0]->submit($declined, false);
    check($declined->kicks === 1 && count($declined->forms) === 1, 'decline kicks without reopening');

    $closed = new Player('ClosedPlayer');
    $plugin->onJoin(new PlayerJoinEvent($closed));
    $closed->forms[0]->close($closed);
    check($closed->kicks === 1 && count($closed->forms) === 1, 'close kicks without reopening');

    $cancelled = new Player('CancelledKick');
    $cancelled->allowKick = false;
    $plugin->onJoin(new PlayerJoinEvent($cancelled));
    $cancelled->forms[0]->submit($cancelled, false);
    check($cancelled->kicks === 1 && count($cancelled->forms) === 2, 'cancelled kick reopens rules');

    $config->values['KickMode'] = false;
    $retry = new Player('RetryPlayer');
    $plugin->onJoin(new PlayerJoinEvent($retry));
    $retry->forms[0]->submit($retry, false);
    check($retry->kicks === 0 && count($retry->forms) === 2, 'non-kick mode retries');

    $viewer = new Player('Viewer');
    check($plugin->onCommand($viewer, new Command('advancedrules'), 'advancedrules', []), 'rules command handled');
    check(count($viewer->forms[0]->elements) === 1 && !is_file($folder . 'data/Viewer'), 'rules command is read-only');

    $config->values['TitleForm'] = 7;
    $config->values['Rules'] = [];
    $config->values['AgreeButon'] = false;
    $invalid = new Player('InvalidConfig');
    $plugin->onJoin(new PlayerJoinEvent($invalid));
    $form = $invalid->forms[0];
    check($form->title === 'AdvancedRules', 'invalid title falls back');
    check($form->elements[0] instanceof Label && $form->elements[0]->text === 'Server rules are not configured.', 'invalid rules fall back');
    check($form->elements[1] instanceof Toggle && $form->elements[1]->text === 'I agree', 'invalid toggle falls back');

    echo "PASS: 12 rules acceptance flow checks\n";
}
