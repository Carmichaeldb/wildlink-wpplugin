import React, { useState, useEffect } from 'react';
import PatientModal from './PatientModal';
import '../styles/admin.css';

const PatientList = () => {
  const [patients, setPatients] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [activeTab, setActiveTab] = useState('current');
  const [modalPatient, setModalPatient] = useState(null);

  useEffect(() => {
    fetchPatients();
  }, []);

  const fetchPatients = async () => {
    try {
    const response = await wp.apiFetch({
      path: '/wildlink/v1/patients'
    });
    setPatients(response);
    } catch (err) {
    setError(err.message);
    } finally {
    setLoading(false);
    }
  };

  const isReleased = patient => 
    patient.release_date && patient.release_date !== '0000-00-00';

  const filteredPatients = patients.filter(patient => 
    activeTab === 'current' ? !isReleased(patient) : isReleased(patient)
  );

  const handleDelete = async (patientId) => {
    if (!window.confirm('Are you sure you want to delete this patient?')) return;
    
    try {
      await wp.apiFetch({
        path: `/wildlink/v1/patient/${patientId}`,
        method: 'DELETE'
      });
      setPatients(prev => prev.filter(p => p.patient_id !== patientId));
    } catch (err) {
      console.error('Delete failed:', err);
    }
  };

  const handleView = (patientId) => {
    console.log("Viewing patient", patientId);
    const patient = patients.find(p => p.patient_id === patientId);
    setModalPatient(patient);
  };

  if (loading) return <div>Loading...</div>;

  return (
    <div className="wrap">
      <h1 className="wp-heading-inline">Patients</h1>
      <a href="#" 
         className="page-title-action"
         onClick={() => window.location.href = 'admin.php?page=wildlink-add-patient'}>
        Add New
      </a>

      <ul className="subsubsub">
        <li>
          <a href="#" 
             onClick={() => setActiveTab('current')}
             className={activeTab === 'current' ? 'current' : ''}>
            Current <span className="count">({patients.filter(p => !p.release_date).length})</span>
          </a> |
        </li>
        <li>
          <a href="#"
             onClick={() => setActiveTab('released')}
             className={activeTab === 'released' ? 'current' : ''}>
            Released <span className="count">({patients.filter(p => p.release_date).length})</span>
          </a>
        </li>
      </ul>

      <table className="wp-list-table widefat fixed striped">
        <thead>
          <tr>
            <th>Case Number</th>
            <th>Species</th>
            <th>Date Admitted</th>
            <th>Location</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {filteredPatients.length > 0 ? (
            filteredPatients.map(patient => (
              <tr key={patient.patient_id}>
                <td>{patient.patient_case}</td>
                <td>{patient.species}</td>
                <td>{new Date(patient.date_admitted).toLocaleDateString()}</td>
                <td>{patient.location_found}</td>
                <td>
                  <div className="row-actions">
                    <span className="edit">
                      <a href={`admin.php?page=wildlink-add-patient&id=${patient.patient_id}`}>Edit</a> |
                    </span>
                    <span className="view">
                      <a href="#" onClick={() => handleView(patient.patient_id)}>View</a> |
                    </span>
                    <span className="delete">
                      <a href="#" onClick={() => handleDelete(patient.patient_id)} className="submitdelete">Delete</a>
                    </span>
                  </div>
                </td>
              </tr>
            ))
          ) : (
            <tr>
              <td colSpan="5">No patients found.</td>
            </tr>
          )}
        </tbody>
      </table>
      {modalPatient && (
        <PatientModal 
          patient={modalPatient}
          onClose={() => setModalPatient(null)}
        />
      )}
    </div>
  );
};

export default PatientList;