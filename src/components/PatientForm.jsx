import React, { useEffect } from "react";
import Select from "react-select";
import { usePatientForm } from "../hooks/usePatientForm";

const PatientForm = ({ postId }) => {
  const {
    formData,
    options,
    loading,
    error,
    isGenerating,
    needsStoryUpdate,
    handleInputChange,
    handleSelectChange,
    handleMultiSelectChange,
    getSelectedOptions,
    openMediaUploader,
    clearImage,
    handleGenerateStory,
    handleSubmit,
  } = usePatientForm(postId);

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;

  console.log(formData);
  return (
    <div className="patient-form">
      <form
        onSubmit={async (e) => {
          e.preventDefault();
          try {
            await handleSubmit();
          } catch (err) {
            console.error("Save failed:", err);
          }
        }}
        style={{ opacity: isGenerating ? 0.7 : 1 }}
      >
        <div className="form-group">
          <label htmlFor="patient_case">Patient Case</label>
          <input
            type="text"
            name="patient_case"
            id="patient_case"
            value={formData.patient_case}
            onChange={handleInputChange}
            disabled={isGenerating}
          />
          <label 
          id="species-label" 
          htmlFor="species_select"
          >
            Species
          </label>
          <Select
            name="species_id"
            inputId="species_select"
            aria-labelledby="species-label"
            options={options.species_options.map((specie) => ({
              value: specie.id,
              label: specie.label,
            }))}
            value={
              formData.species_id
                ? {
                    value: formData.species_id,
                    label: options.species_options.find(
                      (s) => s.id === formData.species_id
                    )?.label,
                  }
                : null
            }
            onChange={handleSelectChange}
            isSearchable
            disabled={isGenerating}
          />
        </div>
        <div className="form-group">
          <label htmlFor="date_admitted">Date Admitted</label>
          <input
            type="date"
            name="date_admitted"
            id="date_admitted"
            value={formData.date_admitted}
            onChange={handleInputChange}
            disabled={isGenerating}
          />
          <label htmlFor="location_found">Location Found</label>
          <input
            type="text"
            name="location_found"
            id="location_found"
            value={formData.location_found}
            onChange={handleInputChange}
            disabled={isGenerating}
          />
        </div>
        <div className="form-group">
          <label htmlFor="is_released">Is Released</label>
          <input
            type="checkbox"
            name="is_released"
            id="is_released"
            checked={formData.is_released}
            disabled={!formData.is_released}
            onChange={handleInputChange}
            disabled={isGenerating}
          />
          <label htmlFor="release_date">Release Date</label>
          <input
            type="date"
            name="release_date"
            id="release_date"
            value={formData.release_date}
            onChange={handleInputChange}
            disabled={isGenerating}
          />
        </div>
        <div className="form-group">
          <label 
          id="patient-conditions-label"
          htmlFor="patient_conditions"
          >
            Conditions
          </label>
          <Select
            name="patient_conditions"
            inputId="patient_conditions"
            aria-labelledby="patient-conditions-label"
            options={options.conditions_options.map((condition) => ({
              value: condition.id,
              label: condition.label,
            }))}
            value={getSelectedOptions(
              formData.patient_conditions,
              options.conditions_options
            )}
            onChange={handleMultiSelectChange}
            isMulti
            isSearchable
            disabled={isGenerating}
          />
        </div>
        <div className="form-group">
          <label 
          id="patient-treatments-label"
          htmlFor="patient_treatments"
          >
            Treatments
          </label>
          <Select
            name="patient_treatments"
            inputId="patient_treatments"
            aria-labelledby="patient-treatments-label"
            options={options.treatments_options.map((treatment) => ({
              value: treatment.id,
              label: treatment.label,
            }))}
            value={getSelectedOptions(
              formData.patient_treatments,
              options.treatments_options
            )}
            onChange={handleMultiSelectChange}
            isMulti
            isSearchable
            disabled={isGenerating}
          />
        </div>
        <div className="form-group">
          <label htmlFor="patient_image">Patient Image</label>
          <div>
            <button 
              type="button" 
              id="patient_image"
              onClick={openMediaUploader}
              disabled={isGenerating}
            >
              Upload Image
            </button>
            {formData.user_uploaded_image && (
              <button
                type="button"
                onClick={clearImage}
                style={{ marginLeft: "10px" }}
                disabled={isGenerating}
              >
                Clear Image
              </button>
            )}
          </div>
          {formData.patient_image && (
            <div>
              <img
                src={formData.patient_image}
                alt="Patient"
                style={{ maxWidth: "200px" }}
              />
            </div>
          )}
        </div>
        <div className="form-group">
          <label htmlFor="patient_story">Patient Story</label>
          <div style={{ display: 'flex', gap: '10px', marginBottom: '10px' }}>
          {needsStoryUpdate && formData.patient_story && (
          <div className="story-warning" style={{ backgroundColor: '#ff0000', marginTop: '10px' }}>
            Story may need to be regenerated due to changes in patient details
          </div>
          )}

          <button 
            type="button"
            onClick={handleGenerateStory}
            disabled={isGenerating}
            style={{
              backgroundColor: needsStoryUpdate ? '#ff0000' : '#4CAF50'
            }}
          >
            {needsStoryUpdate ? 'Regenerate Story' : 'Generate Story'}
          </button>
          </div>
          <textarea
            id="patient_story"
            name="patient_story"
            value={formData.patient_story || ""}
            onChange={handleInputChange}
            rows="12"
            style={{
              width: "100%",
              minHeight: "300px",
              padding: "12px",
              lineHeight: "1.5",
              fontSize: "14px",
              border: "1px solid #ddd",
              borderRadius: "4px",
              resize: "vertical",
            }}
            placeholder="Generate or Write this patient's story..."
            disabled={isGenerating}
          />
        </div>
        <div className="form-group">
          <button 
          type="submit" 
          className="submit-button"
          disabled={isGenerating}
          >
            Save Patient
          </button>
        </div>
      </form>
    </div>
  );
};

export default PatientForm;
