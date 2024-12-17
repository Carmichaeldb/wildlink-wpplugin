import { useState, useEffect, useMemo } from 'react';

export const usePatientForm = (postId) => {

  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const STORY_CRITICAL_FIELDS = ['species_id', 'patient_conditions', 'patient_treatments'];
  const NON_CRITICAL_FIELDS = ['patient_case', 'location_found', 'date_admitted'];
  const [previousCriticalValues, setPreviousCriticalValues] = useState({});
  const [previousNonCriticalValues, setPreviousNonCriticalValues] = useState({});
  const [hasCriticalChanges, setHasCriticalChanges] = useState(false);
  const [hasNonCriticalChanges, setHasNonCriticalChanges] = useState([]);
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

  const currentCriticalValues = useMemo(() => ({
    species_id: formData.species_id,
    patient_conditions: formData.patient_conditions,
    patient_treatments: formData.patient_treatments
  }), [formData.species_id, formData.patient_conditions, formData.patient_treatments]);

  const currentNonCriticalValues = useMemo(() => ({
    patient_case: formData.patient_case,
    location_found: formData.location_found,
    date_admitted: formData.date_admitted,
  }), [formData.patient_case, formData.location_found, formData.date_admitted]);

  // Load initial data
  useEffect(() => {
    const loadData = async () => {
      try {
        const response = await wp.apiFetch({
          path: `/wildlink/v1/patient/${postId}`,
          method: 'GET'
        });

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

        setPreviousNonCriticalValues({
          patient_case: response.patient?.patient_case || '',
          location_found: response.patient?.location_found || '',
          date_admitted: response.patient?.date_admitted || '',
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
    const { name, value } = e.target;
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

  const areArraysEqual = (a, b) => {
    if (!Array.isArray(a) || !Array.isArray(b)) return false;
    if (a.length !== b.length) return false;
    return a.every((item, index) => item === b[index]);
  };

  const getWarningMessageField = (field) => {
    const messageFields = {
      'species_id': 'Species',
      'patient_conditions': 'Conditions',
      'patient_treatments': 'Treatments',
      'patient_case': 'Patient Case',
      'location_found': 'Location Found',
      'date_admitted': 'Date Admitted'
    }
    return messageFields[field] || field;
  };

  useEffect(() => {
    if (!formData.patient_story) return;
  
    const hasChanges = STORY_CRITICAL_FIELDS.filter(field => {
      // Direct comparison for species_id
      if (field === 'species_id') {
        return previousCriticalValues[field] !== currentCriticalValues[field];
      }
      // Array comparison for arrays of conditions and treatments
      return !areArraysEqual(
        previousCriticalValues[field], 
        currentCriticalValues[field]
      );
    })
    .map(field => getWarningMessageField(field));
  
    setHasCriticalChanges(hasChanges);
  }, [currentCriticalValues, previousCriticalValues]);

  useEffect(() => {
    if (!formData.patient_story) return;
  
    const hasChanges = NON_CRITICAL_FIELDS
    .filter(field => previousNonCriticalValues[field] !== currentNonCriticalValues[field])
    .map(field => getWarningMessageField(field));
  
    setHasNonCriticalChanges(hasChanges);
    console.log(hasChanges);
  }, [currentNonCriticalValues, previousNonCriticalValues]);

  const escapeRegExp = (string) => {
    return string.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  };

  const handleUpdateStory = () => {
  
    const meta = currentNonCriticalValues; // Current values to replace with
    const { patient_story } = formData;
  
    if (!patient_story) {
      console.error("No story to update.");
      return;
    }
  
    let updatedStory = patient_story;
  
    Object.entries(meta).forEach(([key, newValue]) => {
      const oldValue = previousNonCriticalValues[key]; // Get the previous value for this field
  
      if (key === "date_admitted" && newValue && oldValue) {
        // Parse new date components (YYYY-MM-DD)
        const [newYear, newMonth, newDay] = newValue.split("-").map(Number);
        const monthNames = {
          long: [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
          ],
          short: [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
          ]
        };
        const datePattern = {
          regex: /(?:(\d{1,2})\s+(January|February|March|April|May|June|July|August|September|October|November|December|Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)|(January|February|March|April|May|June|July|August|September|October|November|December|Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+(\d{1,2}))(?:,?\s+\d{4})?|(?:(\d{4})\/(\d{2})\/(\d{2}))|(?:(\d{1,2})\/(\d{1,2})(?:\/\d{4})?)/g,
          format: (match, dayFirst, monthFirst, monthSecond) => {
            if (monthFirst || monthSecond) {
              const oldMonth = monthFirst || monthSecond;
              const isShortMonth = oldMonth.length <= 3;
              const newMonthName = isShortMonth ? 
                monthNames.short[newMonth - 1] : 
                monthNames.long[newMonth - 1];
              const hasYear = match.includes(oldValue.split("-")[0]);
              
              return dayFirst ? 
                `${newDay} ${newMonthName}${hasYear ? ` ${newYear}` : ''}` :
                `${newMonthName} ${newDay}${hasYear ? `, ${newYear}` : ''}`;
            }
            else {
              return `${String(newDay).padStart(2, '0')}/${String(newMonth).padStart(2, '0')}/${newYear}`;
            }
          }
        };
      
        updatedStory = updatedStory.replace(datePattern.regex, datePattern.format);
        
      } else if (newValue && oldValue && newValue !== oldValue) {
        // Create a regex to match the old value (case-insensitive, exact match)
        const regex = new RegExp(escapeRegExp(oldValue), "gi");
        // Replace old value with new value in the story
        updatedStory = updatedStory.replace(regex, newValue);
      } else {
        console.log(`No change for "${key}": "${oldValue}" remains unchanged.`);
      }
    });

    setFormData((prevData) => ({
      ...prevData,
      patient_story: updatedStory,
    }));

    setPreviousNonCriticalValues(currentNonCriticalValues);
    setHasNonCriticalChanges([]);
  };
  

  const handleGenerateStory = async () => {
    let timeoutId;
  
    try {
      setIsGenerating(true);
      
      // Single state update for loading
      const loadingData = {
        ...formData,
        patient_story: "We are writing the story please wait..."
      };
      setFormData(loadingData);
  
      // Cancelable timeout
      await new Promise((resolve, reject) => {
        timeoutId = setTimeout(resolve, 2000);
      });
      // Test story
      const TEST_STORY = `In the serene vicinity of Campbell River, an sub-adult bald eagle, once a sovereign of the skies, was discovered in a precarious state, burdened by the limitations imposed by a broken wing. Admitted to the wildlife rehabilitation hospital on March 28, 2024, under case number BAEA 083, this noble creature faced a challenging ordeal, contending not only with the physical hindrances of its injury but also with infection and emaciation, a trio of hardships that severely tested its fortitude. The rehabilitation staff, aware of the critical care required, promptly devised a comprehensive treatment regimen aimed at mending its body and spirit.
  
The pathway to recovery for this majestic eagle was carefully charted by the dedicated rehabilitation staff, incorporating an array of interventions including physiotherapy to rehabilitate its weakened muscles, a wing wrap to support the healing process of the broken bone, and fluid therapy coupled with nutritional support to counteract its state of dehydration and malnutrition. Crucially, antibiotics were administered to combat the infection that plagued it, an essential component of the treatment plan that addressed the immediate threat to its well-being. Day by day, the eagle's resilience, bolstered by the unwavering commitment of the rehabilitation staff, signified a gradual return to its innate strength, signaling a hopeful journey towards its eventual reintegration into the wild, a testament to the collective efforts of those dedicated to the preservation of nature's magnificence.`;
      
      const finalData = {
        ...formData,
        patient_story: TEST_STORY
      };
      await handleSubmit(finalData);

      setFormData(finalData);
    setPreviousCriticalValues({
      species_id: finalData.species_id,
      patient_conditions: finalData.patient_conditions,
      patient_treatments: finalData.patient_treatments
    });
    setHasCriticalChanges(false);

  } catch (error) {
    console.error('Failed to generate story:', error);
    setError('Failed to generate story: ' + error.message);
  } finally {
    setIsGenerating(false);
    if (timeoutId) clearTimeout(timeoutId);
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
    hasCriticalChanges,
    hasNonCriticalChanges,
    handleInputChange,
    handleSelectChange,
    handleMultiSelectChange,
    getSelectedOptions,
    openMediaUploader,
    clearImage,
    handleUpdateStory,
    handleGenerateStory,
    handleSubmit
  };
};