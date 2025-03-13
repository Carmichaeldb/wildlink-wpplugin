// src/components/PatientModal.jsx
import React, { useRef } from "react";

const PatientModal = ({ patient, onClose }) => {
  const settings = window.wildlinkData?.settings || {};
  const modalRef = useRef();

  // format date
  const formatDate = (dateString) => {
    const date = new Date(dateString + "T12:00:00Z");
    const wpTimezoneOffset = window.wildlinkData?.timezoneOffset || 0;
    date.setMinutes(date.getMinutes() + wpTimezoneOffset);

    return date.toLocaleDateString("en-US", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  };

  // close modal on overlay click
  const handleOverlayClick = (e) => {
    if (e.target === e.currentTarget) {
      onClose();
    }
  };

  return (
    // modal overlay
    <div className="patient-modal-overlay" onClick={handleOverlayClick}>
      <div
        className="wp-block-wildlink-modal"
        id="wildlink-patient-modal"
        ref={modalRef}
      >
        {/* close button */}
        <button
          className="patient-modal-close"
          onClick={onClose}
          aria-label="Close modal"
        >
          ×
        </button>
        {/* modal content */}
        <div className="patient-modal-content">
          <div className="patient-modal-header">
            <div className="patient-info">
              <div className="patient-details">
                {/* patient case */}
                <h2>{patient.patient_case}</h2>
                {/* species */}
                <p>
                  <strong>Species:</strong> {patient.species}
                </p>
                {/* date admitted */}
                <p>
                  <strong>Date Admitted:</strong>{" "}
                  {formatDate(patient.date_admitted)}
                </p>
                {/* location found */}
                <p>
                  <strong>Location Found:</strong> {patient.location_found}
                </p>
              </div>
              {/* patient image */}
              <div className="patient-image">
                <img src={patient.patient_image} alt={patient.species} />
              </div>
            </div>
          </div>
          {/* release status */}
          {patient.release_date && (
            <div className="patient-release-status">
              <p>Released On: {formatDate(patient.release_date)}</p>
            </div>
          )}

          {/* patient story */}
          <div className="patient-story">
            {patient.patient_story.split("\n\n").map((paragraph, idx) => (
              <p key={idx}>{paragraph}</p>
            ))}
          </div>
          {/* story dates */}
          <div className="wildlink-story-metadata">
            <small>
              Story created:{" "}
              {new Date(patient.story_created_at).toLocaleDateString()}
              {patient.story_updated_at !== patient.story_created_at && (
                <>
                  <br />
                  Last updated:{" "}
                  {new Date(patient.story_updated_at).toLocaleDateString()}
                </>
              )}
            </small>
          </div>
          {/* donation section */}
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
        </div>
      </div>
    </div>
  );
};

export default PatientModal;
