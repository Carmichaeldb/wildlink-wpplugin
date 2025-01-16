import React, { useState, useEffect } from 'react';

const Settings = () => {
  const defaultPrompt = `Create a hopeful 2 paragraph narrative about a wild animal currently being treated at our wildlife rehabilitation center, focusing on the patient's resilience and recovery. 
Do not refer to the center's location. Details:
- Case Number: {patient_case}
- Species: {species}
- Found at: {location_found}
- Admission Date: {date_admitted}
- Age Range: {age_range}
- Conditions: {conditions}
- Required Treatments: {treatments}

Emphasize the dedicated care by our rehabilitation staff and volunteers using general terms like "team members". 
Ensure realistic timelines based on the admission date for treatments immediate and future.
The story should inspire support through its focus on the animal's recovery process. 
Avoid using specific names or locations beyond what is listed here.`;

  const [settings, setSettings] = useState({
    openai_api_key: '',
    story_prompt_template: '',
    cards_per_page: '9',
    show_release_status: true,
    show_admission_date: true,
    default_species_image: '',
    max_daily_generations: '50'
  });

  const [isSaving, setIsSaving] = useState(false);
  const [message, setMessage] = useState({ type: '', content: '' });

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
                <input
                  type="password"
                  name="openai_api_key"
                  id="openai_api_key"
                  value={settings.openai_api_key}
                  onChange={handleInputChange}
                  className="regular-text"
                />
                <p className="description">
                  Enter your OpenAI API key. Get one at <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer">OpenAI's website</a>
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
                  Available placeholders: {'{patient_case}, {species}, {age_range}, {location_found}, {date_admitted}, {conditions}, {treatments}'}
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
                <fieldset>
                  <label>
                    <input
                      type="checkbox"
                      name="show_release_status"
                      checked={settings.show_release_status}
                      onChange={handleInputChange}
                    />
                    Show release status on patient cards
                  </label>
                  <br />
                  <label>
                    <input
                      type="checkbox"
                      name="show_admission_date"
                      checked={settings.show_admission_date}
                      onChange={handleInputChange}
                    />
                    Show admission date on patient cards
                  </label>
                </fieldset>
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
