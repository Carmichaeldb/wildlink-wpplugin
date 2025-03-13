import React from "react";
import "../styles/ColorPreview.css";

const ColorPreview = ({ colors }) => {
  return (
    <div className="wildlink-preview-container">
      <h3>Preview: How Your Patients Will Display</h3>
      <p className="wildlink-preview-description">
        This preview shows how your patient cards and story popup will look with
        your selected colors. Changes will apply after saving settings.
      </p>

      <div className="wildlink-preview-layout">
        <div
          className="wildlink-card-preview"
          style={{ backgroundColor: colors.background }}
        >
          <div className="wildlink-card-image">Patient Image</div>

          <div className="wildlink-card-content" style={{ color: colors.text }}>
            <h3
              className="wildlink-card-title"
              style={{ color: colors.text || "#1e88e5" }}
            >
              WL-2023-142
            </h3>

            <div className="wildlink-card-species">
              <p style={{ color: colors.text || "#1e88e5" }}>Raccoon</p>
            </div>

            <p className="wildlink-card-status">
              <span
                className="wildlink-status-badge"
                style={{
                  backgroundColor: colors.inCareBg,
                  color: colors.inCareText,
                }}
              >
                In Care
              </span>
            </p>

            <button
              className="wildlink-card-button"
              style={{
                backgroundColor: colors.buttonBg,
                color: colors.buttonText,
              }}
            >
              Read My Story
            </button>
          </div>
        </div>

        <div
          className="wildlink-modal-preview"
          style={{ backgroundColor: colors.background, color: colors.text }}
        >
          <div
            className="wildlink-modal-close"
            style={{
              backgroundColor: colors.buttonBg,
              color: colors.buttonText,
            }}
          >
            ✕
          </div>

          <div className="wildlink-modal-header">
            <div className="wildlink-modal-info">
              <h2
                className="wildlink-modal-title"
                style={{ color: colors.text }}
              >
                WL-2023-142
              </h2>

              <p className="wildlink-modal-detail">
                <strong style={{ color: colors.text || "#1e88e5" }}>
                  Species:
                </strong>{" "}
                Raccoon
              </p>
              <p className="wildlink-modal-detail">
                <strong style={{ color: colors.text || "#1e88e5" }}>
                  Admitted:
                </strong>{" "}
                Saturday, June 15, 2024
              </p>
              <p className="wildlink-modal-detail">
                <strong style={{ color: colors.text || "#1e88e5" }}>
                  Location:
                </strong>{" "}
                Backyard in town
              </p>
            </div>

            <div className="wildlink-modal-image">Patient Image</div>
          </div>

          <div className="wildlink-modal-status-container">
            <div
              className="wildlink-status-badge"
              style={{
                backgroundColor: colors.releasedBg || "#c8e6c9",
                color: colors.releasedText || "#2e7d32",
              }}
            >
              Released
            </div>
          </div>

          <div className="wildlink-modal-body">
            <p className="wildlink-modal-story">
              This orphaned raccoon was found alone near a busy road. After
              examination, our team determined the baby was dehydrated and
              needed specialized care. The raccoon has been responding well to
              treatment and is now eating on its own.
            </p>

            <div
              className="wildlink-donation-box"
              style={{
                backgroundColor: colors.donationBg,
                color: colors.donationText,
              }}
            >
              <strong>Your donation makes a difference!</strong>
              <br />
              Help us continue caring for wildlife patients like this raccoon.
            </div>

            <div className="wildlink-donation-button-container">
              <button
                className="wildlink-donation-button"
                style={{
                  backgroundColor: colors.buttonBg,
                  color: colors.buttonText,
                }}
              >
                Donate Now
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ColorPreview;
