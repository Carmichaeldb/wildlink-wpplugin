import { useState, useEffect } from 'react';

export const usePatientForm = (postId) => {

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [formData, setFormData] = useState({
    patient_case: '',
    species_id: '',
    date_admitted: '',
    location_found: '',
    is_released: false,
    release_date: '',
    patient_image: '',
    patient_conditions: [],
    patient_treatments: []
  });
  const [options, setOptions] = useState({
    species_options: [],
    conditions_options: [],
    treatments_options: []
  });

  // Load initial data
  useEffect(() => {
    const loadData = async () => {
      try {
        const response = await wp.apiFetch({
          path: `/wildlink/v1/patient/${postId}`,
          method: 'GET'
        });
        console.log('Response:', response);
        setFormData({
          patient_case: response.patient?.patient_case || '',
          species_id: response.patient?.species_id || '',
          date_admitted: response.patient?.date_admitted || '',
          location_found: response.patient?.location_found || '',
          is_released: response.patient?.release_date && response.patient?.release_date !== '0000-00-00',
          release_date: response.patient?.release_date || '',
          patient_image: response.patient?.patient_image || '',
          patient_conditions: response.patient_conditions || [],
          patient_treatments: response.patient_treatments || [],
          // Check if patient image differs from species image
          user_uploaded_image: response.patient?.patient_image && 
          response.patient?.patient_image !== response.species_options?.find(s => s.id === response.patient?.species_id)?.image
        });
        
        setOptions({
          species_options: response.species_options || [],
          conditions_options: response.conditions_options || [],
          treatments_options: response.treatments_options || []
        });
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    if (postId) {
      loadData();
    }
  }, [postId]);

  // Set default image when species changes if no image is uploaded
  useEffect(() => {
    if (!formData.user_uploaded_image && formData.species_id) {
      const species = options.species_options.find(specie => specie.id === formData.species_id);
      if (species) {
        setFormData(prevData => ({
          ...prevData,
          patient_image: species.image,
        }));
      }
    }
  }, [formData.species_id, formData.user_uploaded_image, options.species_options]);
  
  //tracks input changes
  const handleInputChange = (e) => {
    const { name, value, type } = e.target;
    if (name === 'is_released') {
      // When checkbox is unchecked, clear release date
      setFormData(prevData => ({
        ...prevData,
        is_released: !prevData.is_released,
        release_date: !prevData.is_released ? prevData.release_date : ''
      }));
    } else if (name === 'release_date') {
      // When date changes, update both fields
      setFormData(prevData => ({
        ...prevData,
        release_date: value,
        is_released: value !== '' && value !== '0000-00-00'
      }));
    } else {
      // Handle other inputs normally
      setFormData(prevData => ({
        ...prevData,
        [name]: value
      }));
    }
  };

  //tracks changes in single select drop downs
  const handleSelectChange = (selectedOption, actionMeta) => {
    setFormData((prevData) => {
      const newFormData = {
        ...prevData,
        [actionMeta.name]: selectedOption ? selectedOption.value : "",
      };
      if (actionMeta.name === 'species_id' && !prevData.patient_image) {
        const species = options.species_options.find(specie => specie.id === selectedOption.value); // Use options instead of initialData
        if (species) {
          newFormData.patient_image = species.image;
        }
      }
      return newFormData;
    });
  };
  
  //tracks changes in multi select drop downs
  const handleMultiSelectChange = (selectedOptions, actionMeta) => {
    const { name } = actionMeta;
    setFormData((prevData) => ({
      ...prevData,
      [name]: selectedOptions ? selectedOptions.map((option) => option.value) : [],
    }));
    console.log(`Updated ${name}:`, selectedOptions ? selectedOptions.map((option) => option.value) : []);
  };

  //Displays selected options
  const getSelectedOptions = (selectedIds, optionsList) => {
    return optionsList.filter(option => selectedIds.includes(option.id)).map(option => ({
      value: option.id,
      label: option.label || option.label,
    }));
  };

  //Uploads images
  const openMediaUploader = () => {
    const mediaUploader = wp.media({
      title: 'Choose Image',
      button: {
        text: 'Choose Image'
      },
      multiple: false
    });

    mediaUploader.on('select', () => {
      const attachment = mediaUploader.state().get('selection').first().toJSON();
      setFormData((prevData) => ({
        ...prevData,
        patient_image: attachment.url,
        user_uploaded_image: true,
      }));
    });

    mediaUploader.open();
  };

  //Clears image
  const clearImage = () => {
    setFormData(prevData => ({
      ...prevData,
      patient_image: options.species_options.find(s => s.id === formData.species_id)?.image || '',
      user_uploaded_image: false
    }));
  };

  //Submits form data
  const handleSubmit = async () => {
    try {
      await wp.apiFetch({
        path: `/wildlink/v1/patient/${postId}`,
        method: 'POST',
        data: {
          ...formData,
          user_uploaded_image: formData.patient_image !== options.species_options.find(s => s.id === formData.species_id)?.image
        }
      });
    } catch (err) {
      setError(err.message);
      throw err;
    }
  };

  return {
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
  };
};