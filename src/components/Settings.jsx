import React, { useState, useEffect } from 'react';

const Settings = () => {
  const [settings, setSettings] = useState({});

  const [isSaving, setIsSaving] = useState(false);
  const [message, setMessage] = useState({ type: '', content: '' });
  const [showApiKey, setShowApiKey] = useState(false);

  useEffect(() => {
    loadSettings();
  }, []);

  const loadSettings = async () => {
    try {
      const response = await wp.apiFetch({
        path: '/wildlink/v1/settings'
      });
      setSettings(response);
    } catch (error) {
      setMessage({ type: 'error', content: 'Failed to load settings' });
    }
  };

  const availableModels = [
    { value: 'gpt-4o', label: 'GPT-4o (Most Capable)', description: 'Best quality, most reliable for complex tasks' },
    { value: 'gpt-4o-mini', label: 'GPT-4o-mini (Most cost-effective)', description: 'Faster and more cost-effective' }
  ];

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSaving(true);
    try {
      await wp.apiFetch({
        path: '/wildlink/v1/settings',
        method: 'POST',
        data: settings
      });
      setMessage({ type: 'success', content: 'Settings saved successfully!' });
    } catch (error) {
      setMessage({ type: 'error', content: 'Failed to save settings' });
    }
    setIsSaving(false);
  };

  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setSettings(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
  };

  const handleResetPrompt = async () => {
    try {
      const response = await wp.apiFetch({
        path: '/wildlink/v1/settings/defaults',
        method: 'GET'
      });
      
      if (response.story_prompt_template) {
        setSettings(prev => ({
          ...prev,
          story_prompt_template: response.story_prompt_template
        }));
        setMessage({ type: 'info', content: 'Prompt template reset to default. Don\'t forget to save your changes!' });
      }
    } catch (error) {
      setMessage({ type: 'error', content: 'Failed to reset prompt template' });
    }
  };

  return (
    <div className="wrap">
      <h1>WildLink Settings</h1>
      {message.content && (
        <div className={`notice notice-${message.type} is-dismissible`}>
          <p>{message.content}</p>
        </div>
      )}
      <form onSubmit={handleSubmit}>
        <table className="form-table">
          <tbody>
            <tr>
              <th scope="row">
                <label htmlFor="openai_api_key">OpenAI API Key</label>
              </th>
              <td>
                <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                  <input
                    type={showApiKey ? "text" : "password"}
                    name="openai_api_key"
                    id="openai_api_key"
                    value={settings.openai_api_key}
                    onChange={handleInputChange}
                  className="regular-text"
                />
                <button type="button" 
                    id="toggle_api_key" 
                    class="button" 
                    onClick={() => setShowApiKey(!showApiKey)}
                    >
                    {showApiKey ? 'Hide' : 'Show'}
                    </button>
                </div>
                <p className="description">
                  Enter your OpenAI API key. Get one at <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer">OpenAI's website</a>
                </p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                  <label htmlFor="ai_model">AI Model</label>
              </th>
              <td>
                  <select
                      name="ai_model"
                      id="ai_model"
                      value={settings.ai_model}
                      onChange={handleInputChange}
                      className="regular-text"
                  >
                      {availableModels.map(model => (
                          <option key={model.value} value={model.value}>
                              {model.label}
                          </option>
                      ))}
                  </select>
                  <p className="description">
                      {availableModels.find(m => m.value === settings.ai_model)?.description}
                  </p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label htmlFor="story_prompt_template">Story Prompt Template</label>
              </th>
              <td>
                <textarea
                  name="story_prompt_template"
                  id="story_prompt_template"
                  rows="10"
                  className="large-text code"
                  value={settings.story_prompt_template}
                  onChange={handleInputChange}
                />
                <p>
                  <button 
                    type="button" 
                    className="button button-secondary"
                    onClick={handleResetPrompt}
                    style={{ marginTop: '8px' }}
                  >
                    Reset to Default Prompt
                  </button>
                </p>
                <p className="description">
                  Available placeholders: {'{patient_case}, {species}, {age}, {location_found}, {date_admitted}, {days_in_care}, {current_date}, {conditions}, {treatments}'}
                </p>
              </td>
            </tr>
            <tr>
              <th scope="row">Donation Settings</th>
              <td>
                <div className="wildlink-form-field">
                  <label htmlFor="donation_url">Donation URL</label>
                  <input
                    type="url"
                    id="donation_url"
                    name="donation_url"
                    value={settings.donation_url || ''}
                    onChange={handleInputChange}
                  />
                  <small>Enter the URL where users can make donations</small>
                </div>

                <div className="wildlink-form-field">
                  <label htmlFor="donation_message">Donation Message</label>
                  <input
                    type="text"
                    id="donation_message"
                    name="donation_message"
                    value={settings.donation_message || ''}
                    onChange={handleInputChange}
                  />
                  <small>Message to display with the donation button</small>
                </div>
              </td>
            </tr>
            <tr>
              <th scope="row">Display Settings</th>
              <td>
                <input
                    type="number"
                    name="cards_per_page"
                    id="cards_per_page"
                    min="1"
                    max="100"
                    value={settings.cards_per_page}
                    onChange={handleInputChange}
                    className="small-text"
                />
                <p className="description">
                    Number of patient cards to display per page in the list view.
                </p>
              </td>
            </tr>
          </tbody>
        </table>
        <p className="submit">
          <button 
            type="submit" 
            className="button button-primary"
            disabled={isSaving}
          >
            {isSaving ? 'Saving...' : 'Save Settings'}
          </button>
        </p>
      </form>
    </div>
  );
};

export default Settings;
