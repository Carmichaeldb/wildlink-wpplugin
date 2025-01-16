// src/components/PatientModal.jsx
import React, { useState } from 'react';

const PatientModal = ({ patient, onClose }) => {
  const settings = window.wildlinkData?.settings || {};
  console.log('Settings:', settings);

  console.log('Modal rendering with patient:', patient);
    return (
      <div className="patient-modal-overlay">
        <div className="wp-block-wildlink-modal patient-modal">
          <div className="patient-modal-header">
            <div className="patient-info">
              <div className="patient-details">
                <h2>Case Number: {patient.patient_case}</h2>
                <p><strong>Species:</strong> {patient.species}</p>
                <p><strong>Date Admitted:</strong> {new Date(patient.date_admitted).toLocaleDateString('en-US', { 
                  weekday: 'long',
                  year: 'numeric',
                  month: 'long',
                  day: 'numeric'
                })}</p>
                <p><strong>Location Found:</strong> {patient.location_found}</p>
              </div>
              <div className="patient-image">
                <img src={patient.patient_image} alt={patient.species} />
              </div>
            </div>
          </div>
  
          {patient.release_date && (
            <div className="patient-release-status">
              <p>Released On: {new Date(patient.release_date).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
              })}</p>
            </div>
          )}
  
          <div className="patient-story">
            {patient.patient_story.split('\n\n').map((paragraph, idx) => (
              <p key={idx}>{paragraph}</p>
            ))}
          </div>
          <div className="wildlink-story-metadata">
            <small>
                Story created: {new Date(patient.story_created_at).toLocaleDateString()}
                {patient.story_updated_at !== patient.story_created_at && (
                    <>
                        <br />
                        Last updated: {new Date(patient.story_updated_at).toLocaleDateString()}
                    </>
                )}
            </small>
          </div>
          {settings.donation_url && (
            <div className="patient-donation-section">
              <p>{settings.donation_message}</p>
              <a 
                href={settings.donation_url}
                target="_blank"
                rel="noopener noreferrer"
                className="patient-donate-button"
              >
                Donate Now
              </a>
            </div>
          )}
          <div className="patient-modal-footer">
            <button 
              type="button" 
              className="button-secondary"
              onClick={onClose}
            >
              Close
            </button>
          </div>
        </div>
      </div>
    );
  };
  
  export default PatientModal;