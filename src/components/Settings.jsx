import React, { useState, useEffect } from "react";
import ColorPreview from "./ColorPreview";

const Settings = () => {
  const defaultColors = window.wildlinkData?.defaultColors || {
    // fall back just in case
    text_color: "#333333",
    background_color: "#ffffff",
    donation_background_color: "#f5f5f5",
    donation_text_color: "#333333",
    button_background_color: "#1e88e5",
    button_text_color: "#ffffff",
    releasedBg: "#c8e6c9",
    releasedText: "#2e7d32",
    inCareBg: "#ffdce0",
    inCareText: "#d32f2f",
  };

  // initial settings state
  const [settings, setSettings] = useState({
    openai_api_key: "",
    ai_model: "gpt-4o",
    donation_url: "",
    donation_message: "",
    story_prompt_template: "",
    cards_per_page: 10,
    text_color: defaultColors.text_color,
    background_color: defaultColors.background_color,
    donation_background_color: defaultColors.donation_background_color,
    donation_text_color: defaultColors.donation_text_color,
    button_background_color: defaultColors.button_background_color,
    button_text_color: defaultColors.button_text_color,
    releasedBg: defaultColors.releasedBg,
    releasedText: defaultColors.releasedText,
    inCareBg: defaultColors.inCareBg,
    inCareText: defaultColors.inCareText,
  });

  const [isSaving, setIsSaving] = useState(false);
  const [message, setMessage] = useState({ type: "", content: "" });
  const [showApiKey, setShowApiKey] = useState(false);
  const [previewColors, setPreviewColors] = useState(null);

  // Load settings on mount
  useEffect(() => {
    loadSettings();
  }, []);

  // Update preview when colors change
  useEffect(() => {
    updateColorPreview();
  }, [settings]);

  const updateColorPreview = () => {
    // Create color preview based on current settings
    const colors = {
      text: settings.text_color || defaultColors.text_color,
      background: settings.background_color || defaultColors.background_color,
      donationBg:
        settings.donation_background_color ||
        defaultColors.donation_background_color,
      donationText:
        settings.donation_text_color || defaultColors.donation_text_color,
      buttonBg:
        settings.button_background_color ||
        defaultColors.button_background_color,
      buttonText: settings.button_text_color || defaultColors.button_text_color,
      releasedBg: settings.releasedBg || defaultColors.releasedBg,
      releasedText: settings.releasedText || defaultColors.releasedText,
      inCareBg: settings.inCareBg || defaultColors.inCareBg,
      inCareText: settings.inCareText || defaultColors.inCareText,
    };

    setPreviewColors(colors);
  };

  // load settings
  const loadSettings = async () => {
    try {
      const response = await wp.apiFetch({
        path: "/wildlink/v1/settings",
      });

      if (response) {
        setSettings({
          ...settings,
          ...response,
        });
      }
    } catch (error) {
      console.error("Failed to load settings:", error);
      setMessage({ type: "error", content: "Failed to load settings" });
    }
  };

  // available AI models
  const availableModels = [
    {
      value: "gpt-4o",
      label: "GPT-4o (Most Capable)",
      description: "Best quality, most reliable for complex tasks",
    },
    {
      value: "gpt-4o-mini",
      label: "GPT-4o-mini (Most cost-effective)",
      description: "Faster and more cost-effective",
    },
  ];

  // save settings
  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsSaving(true);

    const settingsToSave = { ...settings };

    try {
      await wp.apiFetch({
        path: "/wildlink/v1/settings",
        method: "POST",
        data: settingsToSave,
      });

      setMessage({ type: "success", content: "Settings saved successfully!" });
    } catch (error) {
      console.error("Error saving settings:", error);
      setMessage({ type: "error", content: "Failed to save settings" });
    }
    setIsSaving(false);
  };

  // handle input change
  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setSettings((prev) => ({
      ...prev,
      [name]: value,
    }));
  };

  // reset prompt template
  const handleResetPrompt = async () => {
    try {
      const response = await wp.apiFetch({
        path: "/wildlink/v1/settings/defaults",
        method: "GET",
      });

      if (response.story_prompt_template) {
        setSettings((prev) => ({
          ...prev,
          story_prompt_template: response.story_prompt_template,
        }));
        setMessage({
          type: "info",
          content:
            "Prompt template reset to default. Don't forget to save your changes!",
        });
      }
    } catch (error) {
      setMessage({ type: "error", content: "Failed to reset prompt template" });
    }
  };

  // reset colors
  const handleResetColors = () => {
    setSettings((prev) => ({
      ...prev,
      text_color: defaultColors.text_color,
      background_color: defaultColors.background_color,
      donation_background_color: defaultColors.donation_background_color,
      donation_text_color: defaultColors.donation_text_color,
      button_background_color: defaultColors.button_background_color,
      button_text_color: defaultColors.button_text_color,
      releasedBg: defaultColors.releasedBg,
      releasedText: defaultColors.releasedText,
      inCareBg: defaultColors.inCareBg,
      inCareText: defaultColors.inCareText,
    }));

    setMessage({
      type: "info",
      content:
        "Colors reset to plugin defaults. Don't forget to save your changes!",
    });
  };

  return (
    <div className="wrap">
      {/* message on save or resets */}
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
                {/* openai api key */}
                <label htmlFor="openai_api_key">OpenAI API Key</label>
              </th>
              <td>
                <div
                  style={{ display: "flex", alignItems: "center", gap: "10px" }}
                >
                  <input
                    type={showApiKey ? "text" : "password"}
                    name="openai_api_key"
                    id="openai_api_key"
                    value={settings.openai_api_key || ""}
                    onChange={handleInputChange}
                    className="regular-text"
                  />
                  <button
                    type="button"
                    id="toggle_api_key"
                    className="button"
                    onClick={() => setShowApiKey(!showApiKey)}
                  >
                    {showApiKey ? "Hide" : "Show"}
                  </button>
                </div>
                <p className="description">
                  Enter your OpenAI API key. Get one at{" "}
                  <a
                    href="https://platform.openai.com/api-keys"
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    OpenAI's website
                  </a>
                </p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                {/* ai model */}
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
                  {availableModels.map((model) => (
                    <option key={model.value} value={model.value}>
                      {model.label}
                    </option>
                  ))}
                </select>
                <p className="description">
                  {
                    availableModels.find((m) => m.value === settings.ai_model)
                      ?.description
                  }
                </p>
              </td>
            </tr>
            <tr>
              <th scope="row">
                {/* story prompt template */}
                <label htmlFor="story_prompt_template">
                  Story Prompt Template
                </label>
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
                {/* reset prompt */}
                <p>
                  <button
                    type="button"
                    className="button button-secondary"
                    onClick={handleResetPrompt}
                    style={{ marginTop: "8px" }}
                  >
                    Reset to Default Prompt
                  </button>
                </p>
                {/* prompt info */}
                <p className="description">
                  Available placeholders:{" "}
                  {
                    "{patient_case}, {species}, {age}, {location_found}, {date_admitted}, {days_in_care}, {current_date}, {conditions}, {treatments}"
                  }
                </p>
              </td>
            </tr>
            <tr>
              {/* donation settings */}
              <th scope="row">Donation Settings</th>
              <td>
                <div className="wildlink-form-field">
                  {/* donation url */}
                  <label htmlFor="donation_url">Donation URL</label>
                  <input
                    type="url"
                    id="donation_url"
                    name="donation_url"
                    value={settings.donation_url || ""}
                    onChange={handleInputChange}
                  />
                  <small>Enter the URL where users can make donations</small>
                </div>
                {/* donation message */}
                <div className="wildlink-form-field">
                  <label htmlFor="donation_message">Donation Message</label>
                  <input
                    type="text"
                    id="donation_message"
                    name="donation_message"
                    value={settings.donation_message || ""}
                    onChange={handleInputChange}
                  />
                  <small>Message to display with the donation button</small>
                </div>
              </td>
            </tr>
            <tr>
              {/* card pagination settings */}
              <th scope="row">Display Settings</th>
              <td>
                {/* cards per page */}
                <input
                  type="number"
                  name="cards_per_page"
                  id="cards_per_page"
                  min="3"
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
            <tr>
              {/* color settings */}
              <th scope="row">Appearance Settings</th>
              <td>
                <div className="wildlink-color-settings">
                  {/* reset colors */}
                  <button
                    type="button"
                    className="button button-secondary"
                    onClick={handleResetColors}
                    style={{ marginTop: "8px" }}
                  >
                    Reset to Default Colors
                  </button>
                </div>
              </td>
            </tr>
            <tr>
              {/* text color */}
              <th scope="row">Text Color</th>
              <td>
                <input
                  type="color"
                  name="text_color"
                  value={settings.text_color || defaultColors.text_color}
                  onChange={handleInputChange}
                />
                <span style={{ marginLeft: "10px" }}>
                  Used for story content and normal text
                </span>
              </td>
            </tr>
            <tr>
              {/* background color */}
              <th scope="row">Background Color</th>
              <td>
                <input
                  type="color"
                  name="background_color"
                  value={
                    settings.background_color || defaultColors.background_color
                  }
                  onChange={handleInputChange}
                />
                <span style={{ marginLeft: "10px" }}>
                  Background color for patient cards and story popups
                </span>
              </td>
            </tr>
            <tr>
              {/* donation background color */}
              <th scope="row">Donation Background</th>
              <td>
                <input
                  type="color"
                  name="donation_background_color"
                  value={
                    settings.donation_background_color ||
                    defaultColors.donation_background_color
                  }
                  onChange={handleInputChange}
                />
                <span style={{ marginLeft: "10px" }}>
                  Background color for donation message boxes
                </span>
              </td>
            </tr>
            <tr>
              {/* donation text color */}
              <th scope="row">Donation Text</th>
              <td>
                <input
                  type="color"
                  name="donation_text_color"
                  value={
                    settings.donation_text_color ||
                    defaultColors.donation_text_color
                  }
                  onChange={handleInputChange}
                />
                <span style={{ marginLeft: "10px" }}>
                  Text color inside the donation message boxes
                </span>
              </td>
            </tr>
            <tr>
              {/* button background color */}
              <th scope="row">Button Background</th>
              <td>
                <input
                  type="color"
                  name="button_background_color"
                  value={
                    settings.button_background_color ||
                    defaultColors.button_background_color
                  }
                  onChange={handleInputChange}
                />
                <span style={{ marginLeft: "10px" }}>
                  Background color for "Read Full Story" and "Donate Now"
                  buttons
                </span>
              </td>
            </tr>
            <tr>
              {/* button text color */}
              <th scope="row">Button Text</th>
              <td>
                <input
                  type="color"
                  name="button_text_color"
                  value={
                    settings.button_text_color ||
                    defaultColors.button_text_color
                  }
                  onChange={handleInputChange}
                />
                <span style={{ marginLeft: "10px" }}>
                  Text color for button labels
                </span>
              </td>
            </tr>
            <tr>
              {/* released status background */}
              <th scope="row">
                <label htmlFor="releasedBg">Released Status Background</label>
              </th>
              <td>
                <input
                  type="color"
                  id="releasedBg"
                  name="releasedBg"
                  value={settings.releasedBg || "#c8e6c9"}
                  onChange={handleInputChange}
                />
                <span style={{ marginLeft: "10px" }}>
                  Background color for "Released" status
                </span>
              </td>
            </tr>
            <tr>
              {/* released status text */}
              <th scope="row">
                <label htmlFor="releasedText">Released Status Text</label>
              </th>
              <td>
                <input
                  type="color"
                  id="releasedText"
                  name="releasedText"
                  value={settings.releasedText || "#2e7d32"}
                  onChange={handleInputChange}
                />
                <span style={{ marginLeft: "10px" }}>
                  Text color for "Released" status
                </span>
              </td>
            </tr>
            <tr>
              {/* in care status background */}
              <th scope="row">
                <label htmlFor="inCareBg">In Care Status Background</label>
              </th>
              <td>
                <input
                  type="color"
                  id="inCareBg"
                  name="inCareBg"
                  value={settings.inCareBg || "#ffdce0"}
                  onChange={handleInputChange}
                />
                <span style={{ marginLeft: "10px" }}>
                  Background color for "In Care" status
                </span>
              </td>
            </tr>
            <tr>
              {/* in care status text */}
              <th scope="row">
                <label htmlFor="inCareText">In Care Status Text</label>
              </th>
              <td>
                <input
                  type="color"
                  id="inCareText"
                  name="inCareText"
                  value={settings.inCareText || "#d32f2f"}
                  onChange={handleInputChange}
                />
                <span style={{ marginLeft: "10px" }}>
                  Text color for "In Care" status
                </span>
              </td>
            </tr>
          </tbody>
        </table>
        {/* save button */}
        <p className="submit">
          <button
            type="submit"
            className="button button-primary"
            disabled={isSaving}
          >
            {isSaving ? "Saving..." : "Save Settings"}
          </button>
        </p>
      </form>
      {/* color preview */}
      {previewColors && <ColorPreview colors={previewColors} />}
    </div>
  );
};

export default Settings;
