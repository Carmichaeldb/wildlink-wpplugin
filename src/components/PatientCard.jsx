import React from "react";

const PatientCard = ({ patient, onViewStory }) => {
    return (
        <div className="patient-card">
            {/* patient image */}
            <div className="patient-card-image">
                <img 
                    src={patient.patient_image} 
                    alt={`${patient.species}`} 
                />
            </div>
            <div className="patient-card-content">
                {/* patient identifier */}
                <h3>{patient.patient_case}</h3>
                {/* patient species */}
                <p>{patient.species}</p>
                {/* patient status */}
                <div className={`status-badge ${patient.release_date ? 'released' : 'in-care'}`}>
                    {patient.release_date ? 'Released' : 'In Care'}
                </div>
                {/* read my story button */}
                <button 
                    className="button view-story-btn"
                    onClick={() => onViewStory(patient)}
                >
                    Read My Story
                </button>
            </div>
        </div>
    );
};

export default PatientCard;
