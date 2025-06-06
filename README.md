# Lessonation Plugin for Moodle

Lessonation is a local plugin for Moodle designed to dynamically create lessons using AI. This plugin leverages course content, websites, and text to generate engaging and structured lessons, saving educators time and enhancing the learning experience.

## Features

- **AI-Powered Lesson Creation**: Automatically generate lessons based on course materials, external websites, or custom text inputs.
- **Dynamic Content**: Create interactive and engaging lessons tailored to the needs of your learners.
- **Seamless Integration**: Fully integrates with Moodle, ensuring a smooth user experience.
- **Customizable Outputs**: Fine-tune the generated lessons to match your teaching style and objectives.

## Installation

1. Download or clone the plugin into the `local/` directory of your Moodle installation:
    ```bash
    git clone https://github.com/yedidiaklein/moodle-local_lessonation.git lessonation
    ```
2. Navigate to your Moodle site as an administrator to complete the installation process.
3. Configure the plugin settings under the Moodle administration panel.

## Usage

1. Navigate to the Lessonation link on top menu in your Moodle course.
2. Provide the source content (website URLs or subject).
3. Click "Generate Lesson" to create AI-powered lessons.
4. your lesson will be generated using adhoc task and will appear in your course shortly.

## Requirements

- Moodle 4.5 or higher
- AI API key (e.g., OpenAI, Azure AI) in Moodle AI settings.

## Contributing

Contributions are welcome! Please fork the repository and submit a pull request with your improvements.

## License

This plugin is provided freely as open source software, under version 3 of the GNU General Public License.

## Support

For issues or feature requests, please open an issue on the [GitHub repository](https://github.com/yedidiaklein/moodle-local_lessonation).
