import React, { useEffect } from 'react';
import Select from 'react-select';
import { usePatientForm } from '../hooks/usePatientForm';

const PatientForm = ({ postId }) => {

  const {
    formData,
    options,
    loading,
    error,
    handleInputChange,
    handleSelectChange,
    handleMultiSelectChange,
    getSelectedOptions,
    openMediaUploader,
    clearImage,
    handleSubmit
  } = usePatientForm(postId);

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;

  console.log(formData);
  return (
    <div className="patient-form">
      <form onSubmit={async (e) => {
        e.preventDefault();
        try {
          await handleSubmit();
        } catch (err) {
          console.error('Save failed:', err);
        }
      }}>
      <div className="form-group">
        <label htmlFor="patient_case">Patient Case</label>
        <input
          type="text"
          name="patient_case"
          id="patient_case"
          value={formData.patient_case}
          onChange={handleInputChange}
        />
        <label htmlFor="species_id">Species</label>
        <Select
          name="species_id"
          options={options.species_options.map((specie) => ({
            value: specie.id,
            label: specie.label,
          }))}
          value={formData.species_id ? {
            value: formData.species_id,
            label: options.species_options.find(s => s.id === formData.species_id)?.label
          } : null}
          onChange={handleSelectChange}
          isSearchable
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
        />
        <label htmlFor="location_found">Location Found</label>
        <input
          type="text"
          name="location_found"
          id="location_found"
          value={formData.location_found}
          onChange={handleInputChange}
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
        />
        <label htmlFor="release_date">Release Date</label>
        <input
          type="date"
          name="release_date"
          id="release_date"
          value={formData.release_date}
          onChange={handleInputChange}
        />
      </div>
      <div className="form-group">
        <label htmlFor="patient_conditions">Conditions</label>
        <Select
          name="patient_conditions"
          options={options.conditions_options.map((condition) => ({
            value: condition.id,
            label: condition.label,
          }))}
          value={getSelectedOptions(formData.patient_conditions, options.conditions_options)}
          onChange={handleMultiSelectChange}
          isMulti
          isSearchable
        />
      </div>
      <div className="form-group">
        <label htmlFor="patient_treatments">Treatments</label>
        <Select
          name="patient_treatments"
          options={options.treatments_options.map((treatment) => ({
            value: treatment.id,
            label: treatment.label,
          }))}
          value={getSelectedOptions(formData.patient_treatments, options.treatments_options)}
          onChange={handleMultiSelectChange}
          isMulti
          isSearchable
        />
      </div>
      <div className="form-group">
        <label htmlFor="patient_image">Patient Image</label>
        <div>
        <button type="button" onClick={openMediaUploader}>Upload Image</button>
        {formData.user_uploaded_image && (
          <button type="button" onClick={clearImage} style={{ marginLeft: '10px' }}>
            Clear Image
          </button>
        )}
        </div>
        {formData.patient_image && (
          <div>
            <img src={formData.patient_image} alt="Patient" style={{ maxWidth: '200px' }} />
          </div>
        )}
      </div>
      <div className="form-group">
          <button type="submit" className="submit-button">
            Save Patient
          </button>
        </div>
      </form>
    </div>
  );
};

export default PatientForm;