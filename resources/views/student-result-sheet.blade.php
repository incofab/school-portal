<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Result sheet</title>
    <style>
        .avartar{
            width: 100px;
            height: 100px;
            border-radius: 50%;
        }
        .avartar > img{
            width: 100%;
        }
        .vertical-flex {
            display: flex;
            flex-direction: row;
        }
        .horizontal-flex {
            display: flex;
            flex-direction: column;
        }
    </style>
</head>
<body>
    <div style="min-height: 1170px;">
        <div width="900px" style="margin-left: auto; margin-right: auto; padding-left: 10px; padding-right: 10px">
            <div class="horizontal-flex" style="background: #FAFAFA; padding-left: 8px; padding-right: 8px">
              <div class="avartar">
                  <img
                    alt="Institution logo"
                    src={currentInstitution.photo ?? ImagePaths.default_school_logo}
                  />
              </div>
              <div style="align-self: stretch; text-align: center; font-size: 18px; white-space: nowrap">
                <div>
                  {currentInstitution.name}
                </div>
                <div>
                  {currentInstitution.address}
                  <br /> {currentInstitution.email}
                </div>
                <div>
                  {academicSession?.title} - {startCase(termResult.term)} Term
                  Result
                </div>
              </div>
              <div class="avartar">
                  <img alt="Student logo" src={student.user?.photo_url} />
                </div>
            </div>
            <div class="horizontal-flex" style="justify-content: space-between">
              <div><span>Name of Student: </span><span>{student?.user?.full_name}</span></div>
              <div><span>Gender: </span><span>{student?.user?.gender}</span></div>
            </div>
            <div class="horizontal-flex" style="margin: 5px; justify-content: space-between">
              <div><span>Class: </span><span>{classification.title}</span></div>
              <div><span>Position: </span><span>{termResult.position + getPositionSuffix(termResult.position)}</span></div>
              <div><span>Out of: </span><span>{classResultInfo.num_of_students}</span></div>
            </div>
            <div className="table-container">
              <table className="result-table" style="width: 100%">
                <thead>
                  <tr>
                    <th>Subjects</th>
                    <th>
                      <div class="vertical-text">Assessment 1</div>
                    </th>
                    <th>
                      <div class="vertical-text">Assessment 2</div>
                    </th>
                    <th>
                      <div class="vertical-text">Exam</div>
                    </th>
                    <th>
                      <div class="vertical-text">Total</div>
                    </th>
                    <th>
                      <div class="vertical-text">Grade</div>
                    </th>
                    <th>
                      <div class="vertical-text">Position</div>
                    </th>
                    <th>
                      <div class="vertical-text">Class Average</div>
                    </th>
                    <th>Remark</th>
                  </tr>
                </thead>
                <tbody>
                    @foreach ($courseResults as $courseResult)
                        <tr>
                            <td>{$courseResult->course?->title}</td>
                            <td>{$courseResult->first_assessment}</td>
                            <td>{$courseResult->second_assessment}</td>
                            <td>{$courseResult->exam}</td>
                            <td>{$courseResult->result}</td>
                            <td>{$courseResult->grade}</td>
                            <td>{$courseResult->position}</td>
                            <td>
                                {$courseResultInfoData[courseResult.course_id]?->average}
                            </td>
                            <td>{getRemark($courseResult->grade)}</td>
                        </tr>
                    @endforeach
                </tbody>
              </table>
            </div>
            <div>
              <table className="result-analysis-table">
                <tbody>
                    @foreach ($resultDetails as $resultDetail)
                    <tr>
                      <td style="width: 250px">{$resultDetail['label']}</td>
                      <td>{$resultDetail['value']}</td>
                    </tr>
                    @endforeach
                </tbody>
              </table>
            </div>
        </div>
      </div>
</body>
</html>