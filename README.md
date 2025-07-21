![Build Status](https://github.com/DiscipleTools/disciple-tools-ai/actions/workflows/ci.yml/badge.svg?branch=master)

![Plugin Banner](https://raw.githubusercontent.com/DiscipleTools/disciple-tools-ai/master/documentation/banner.png)

# Disciple.Tools - AI

This plugin integrates AI capabilities into Disciple.Tools to help disciple makers in their work by providing intelligent search, filtering, summarization, and mapping features.

## Features

### AI-Powered Search and Filtering
- **Smart List Filtering**: Use natural language prompts to filter contacts, groups, and other records
- **Intelligent Field Recognition**: AI automatically identifies relevant fields from your search queries
- **Multiple Option Resolution**: When AI finds multiple matches for locations, users, or posts, it prompts for clarification
- **(in progress) PII Protection**: Automatically detects and obfuscates personally identifiable information before sending to AI models

### Magic Link Applications
- **AI List App**: A standalone magic link application with AI-powered search and filtering capabilities
- **User Apps**: Pre-configured user applications for field workers and team members
- **Login Apps**: Secure access applications with AI functionality

### Data Visualization
- **Dynamic AI Maps**: AI-generated maps in the metrics section that can visualize filtered data geographically
- **Custom Charts**: Integration with the metrics system for AI-enhanced data visualization

### Future features:

#### Content Summarization
- **Record Summaries**: AI-powered summarization of contact records, activities, and comments
- **Activity Analysis**: Intelligent analysis of user interactions and record updates
- **Custom Tiles**: AI summary tiles integrated into contact and record detail pages
- **(in progress) PII Protection**: Automatically detects and obfuscates personally identifiable information before sending to AI models

#### Magic link Voice instructions to update records
- **Voice-to-Text Transcription**: Record voice commands that are automatically transcribed using AI speech recognition
- **Natural Language Processing**: Speak naturally about meetings and contact updates (e.g., "I met with John yesterday and we discussed his baptism")
- **Intelligent Contact Matching**: AI identifies which contact you're referring to from voice commands
- **Automatic Field Updates**: Voice commands automatically update relevant contact fields like faith status, milestones, and communication details
- **Meeting Notes Integration**: Spoken meeting details are automatically added as comments to contact records
- **Hands-Free Operation**: Perfect for field workers who need to update records while traveling or in situations where typing is difficult



### REST API Integration
- Endpoints for AI summarization (`/dt-ai-summarize`)
- Endpoints for creating AI-powered filters (`/dt-ai-create-filter`)
- Secure permission-based access to AI features

## Requirements

- Disciple.Tools Theme installed on a WordPress Server
- Compatible LLM API endpoint (OpenAI-compatible)
- Valid API key for your chosen LLM provider

## Configuration

### LLM Settings
After installation, configure your AI provider in the admin settings:

1. **LLM Endpoint**: Your AI provider's API endpoint URL
2. **API Key**: Your authentication key for the AI service
3. **Model**: The specific AI model to use (e.g., `gpt-4`, `claude-3`, etc.)

### Module Settings
The plugin includes several configurable modules:

- **List Search and Filter**: Enable AI search and filter for lists
- **List User App (Magic Link)**: A user app with AI search and filter integrated
- **Metrics Dynamic Maps**: AI-powered maps in the metrics section

## Installing

- Install as a standard Disciple.Tools/WordPress plugin in the system Admin/Plugins area
- Requires the user role of Administrator for initial configuration
- Configure LLM settings in Extensions (D.T) > Disciple Tools AI

## Usage Examples

### Natural Language Filtering
- "Show me all contacts in Kenya who are baptized"
- "Find active groups started this year"
- "List contacts assigned to John with recent activity"

### Geographic Queries
- "Contacts in Nairobi with faith milestone of baptized"
- "Groups meeting in urban areas"

### Status and Timeline Queries
- "New contacts from last month"
- "Inactive groups that need follow-up"
- "Contacts baptized this year"

## Privacy and Security (in progress)

- **PII Detection**: Automatically identifies and protects personal information
- **Data Obfuscation**: Sensitive data is masked before being sent to AI providers
- **Permission Controls**: Fine-grained access controls for AI features
- **Secure API**: All endpoints require proper authentication and permissions

## Contribution

Contributions welcome. You can report issues and bugs in the
[Issues](https://github.com/DiscipleTools/disciple-tools-ai/issues) section of the repo. You can present ideas
in the [Discussions](https://github.com/DiscipleTools/disciple-tools-ai/discussions) section of the repo. And
code contributions are welcome using the [Pull Request](https://github.com/DiscipleTools/disciple-tools-ai/pulls)
system for git. For more details on contribution see the
[contribution guidelines](https://github.com/DiscipleTools/disciple-tools-ai/blob/master/CONTRIBUTING.md).
