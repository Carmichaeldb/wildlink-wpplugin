import { useState, useEffect } from 'react';

export const usePatientForm = (postId) => {

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const STORY_CRITICAL_FIELDS = ['species_id', 'patient_conditions', 'patient_treatments'];
  const [needsStoryUpdate, setNeedsStoryUpdate] = useState(false);
  const [previousCriticalValues, setPreviousCriticalValues] = useState({});
  const [isGenerating, setIsGenerating] = useState(false);

  /// FORM DATA MANAGEMENT ///
  const [formData, setFormData] = useState({
    patient_case: '',
    species_id: '',
    date_admitted: '',
    location_found: '',
    is_released: false,
    release_date: '',
    patient_image: '',
    patient_conditions: [],
    patient_treatments: [],
    patient_story: '',
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
          response.patient?.patient_image !== response.species_options?.find(s => s.id === response.patient?.species_id)?.image,
          patient_story: response.patient?.patient_story || '',
        });
        setPreviousCriticalValues({
          species_id: response.patient?.species_id || '',
          patient_conditions: response.patient_conditions || [],
          patient_treatments: response.patient_treatments || []
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
        is_released: value !== ''
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

 /// STORY GENERATION AND MANAGEMENT LOGIC ///

  useEffect(() => {
    if (formData.patient_story) {
      const currentCriticalValues = {
        species_id: formData.species_id,
        patient_conditions: formData.patient_conditions,
        patient_treatments: formData.patient_treatments
      };
  
      const hasChanges = STORY_CRITICAL_FIELDS.some(field => {
        const prev = JSON.stringify(previousCriticalValues[field]);
        const curr = JSON.stringify(currentCriticalValues[field]);
        return prev !== curr;
      });
  
      setNeedsStoryUpdate(hasChanges);
      if (hasChanges) {
        console.log('Story needs update due to critical field changes');
      }
    }
  }, [formData.species_id, formData.patient_conditions, formData.patient_treatments]);

  const handleGenerateStory = async () => {
    try {
      setIsGenerating(true);
      let currentStory = "We are writing the story please wait...";
      setFormData(prev => ({
          ...prev,
          patient_story: currentStory
      }));
  
      // Simulate API delay
      await new Promise(resolve => setTimeout(resolve, 2000));
      // Test story
      currentStory = `In the serene vicinity of Campbell River, an sub-adult bald eagle, once a sovereign of the skies, was discovered in a precarious state, burdened by the limitations imposed by a broken wing. Admitted to the wildlife rehabilitation hospital on March 28, 2024, under case number BAEA 083, this noble creature faced a challenging ordeal, contending not only with the physical hindrances of its injury but also with infection and emaciation, a trio of hardships that severely tested its fortitude. The rehabilitation staff, aware of the critical care required, promptly devised a comprehensive treatment regimen aimed at mending its body and spirit.
  
The pathway to recovery for this majestic eagle was carefully charted by the dedicated rehabilitation staff, incorporating an array of interventions including physiotherapy to rehabilitate its weakened muscles, a wing wrap to support the healing process of the broken bone, and fluid therapy coupled with nutritional support to counteract its state of dehydration and malnutrition. Crucially, antibiotics were administered to combat the infection that plagued it, an essential component of the treatment plan that addressed the immediate threat to its well-being. Day by day, the eagle's resilience, bolstered by the unwavering commitment of the rehabilitation staff, signified a gradual return to its innate strength, signaling a hopeful journey towards its eventual reintegration into the wild, a testament to the collective efforts of those dedicated to the preservation of nature's magnificence.`;
      await handleSubmit({
        ...formData,
        patient_story: currentStory
      });

      setFormData(prev => ({
        ...prev,
        patient_story: currentStory
      }));
      console.log("Story generated and saved");
      setPreviousCriticalValues({
        species_id: formData.species_id,
        patient_conditions: formData.patient_conditions,
        patient_treatments: formData.patient_treatments
      });
      setNeedsStoryUpdate(false);
    } catch (error) {
      console.error('Failed to generate story:', error);
      setError('Failed to generate story: ' + error.message);
    } finally {
      setIsGenerating(false);
    }
  };

  /// PATIENT FORM SUBMISSION ///
  const handleSubmit = async (data = formData) => {
    console.log("submitting...");
    try {
      await wp.apiFetch({
        path: `/wildlink/v1/patient/${postId}`,
        method: 'POST',
        data: {
          ...data,
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
    isGenerating,
    needsStoryUpdate,
    handleInputChange,
    handleSelectChange,
    handleMultiSelectChange,
    getSelectedOptions,
    openMediaUploader,
    clearImage,
    handleGenerateStory,
    handleSubmit
  };
};