import React, { useState, useEffect } from 'react';

const PatientList = () => {
  const [patients, setPatients] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('current');

  useEffect(() => {
    fetchPatients();
  }, []);

  const fetchPatients = async () => {
    const response = await wp.apiFetch({
      path: '/wildlink/v1/patients'
    });
    setPatients(response);
    setLoading(false);
  };

  const filteredPatients = patients.filter(patient => 
    activeTab === 'current' ? !patient.is_released : patient.is_released
  );

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
            Current <span className="count">({patients.filter(p => !p.is_released).length})</span>
          </a> |
        </li>
        <li>
          <a href="#"
             onClick={() => setActiveTab('released')}
             className={activeTab === 'released' ? 'current' : ''}>
            Released <span className="count">({patients.filter(p => p.is_released).length})</span>
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
              <tr key={patient.id}>
                <td>{patient.patient_case}</td>
                <td>{patient.species}</td>
                <td>{new Date(patient.date_admitted).toLocaleDateString()}</td>
                <td>{patient.location_found}</td>
                <td>
                  <div className="row-actions">
                    <span className="edit">
                      <a href={`admin.php?page=wildlink-add-patient&id=${patient.id}`}>Edit</a> |
                    </span>
                    <span className="view">
                      <a href="#" onClick={() => handleView(patient.id)}>View</a> |
                    </span>
                    <span className="delete">
                      <a href="#" onClick={() => handleDelete(patient.id)} className="submitdelete">Delete</a>
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
    </div>
  );
};

export default PatientList;