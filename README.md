# AdvancedRules

AdvancedRules asks first-time players to accept your server rules through a form. It records each accepted player name as a marker file, shows the rules again to players who have not accepted, and provides `/advancedrules` for viewing the rules later.

## Requirements and compatibility

- The plugin declares Axolotl-PM API 5.0.0 in `plugin.yml`.
- The build includes [pmforms](https://github.com/dktapps-pm-pl/pmforms) as a private virion. Install the release PHAR as one file. The server does not need a separate pmforms plugin.
- The source has passed PHPStan at maximum level against Axolotl-PM 5.49.1. The release workflow records the exact source revisions used for each build. Compatibility with every API 5 server patch has not been verified.

## Install and use

1. Download the PHAR from [GitHub Releases](https://github.com/NhanAZ-Plugins/AdvancedRules/releases) and place it in the server's `plugins/` directory.
2. Start the server once to create `plugins/AdvancedRules/config.yml`.
3. Edit `Rules`, the form title, and the messages in that file. Restart the server to apply changes.

A player with no acceptance marker sees the rules form after joining. Accepting creates `plugins/AdvancedRules/data/<player name>`. With `KickMode: true`, declining or closing the form disconnects the player. With `KickMode: false`, the form is shown again until the player accepts. Keep the `data/` directory when updating if you want to preserve existing agreements. Back it up before changing player identity or storage policy.

The `/advancedrules` command, also available as `/rules`, shows the rules without asking for another acceptance. Its permission is `advancedrules.command` and is granted to players by default.

## Configuration

The included [default configuration](resources/config.yml) documents the supported keys. `Rules` supports `§` color codes and `\n` line breaks. `KickMode` controls what happens when a player declines or closes the form. The legacy key `AgreeButon` is intentionally retained so existing configurations continue to work.

## Build and maintenance

The [build workflow](.github/workflows/build.yml) uses [DevTools](https://github.com/NhanAZ/DevTools) to analyze the plugin, generate a standalone PHAR, and validate its checksum. The pmforms Git submodule pins the bundled library to a reviewed commit. Clone with submodules when building locally. Dependabot checks GitHub Actions and the submodule for updates. Proposed updates require CI and runtime review before they are merged.

## License and support

AdvancedRules is distributed under [GPL-3.0](LICENSE). The bundled pmforms library retains its [LGPL-3.0 license](https://github.com/dktapps-pm-pl/pmforms/blob/70082f430c722b868dfdfeab7337ba6b944d21dd/LICENSE) in the PHAR. For support, open a [GitHub issue](https://github.com/NhanAZ-Plugins/AdvancedRules/issues) or visit [NhanAZ Discord](https://discord.gg/j2X83ujT6c).
