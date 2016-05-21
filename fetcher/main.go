package main

import (
	"encoding/json"
	"fmt"
	"time"
	"github.com/robfig/cron"
	"io/ioutil"
	"net/http"
	"errors"
	"database/sql"
	_"github.com/go-sql-driver/mysql"
)

const urlStatsDomains 	= "https://metaverse.highfidelity.com/api/v1/stats/domains"
const urlUsers		= "https://metaverse.highfidelity.com/api/v1/users?status=online"
var insStatsDomains 	*sql.Stmt
var insStatsUsers	*sql.Stmt
var insUsers		*sql.Stmt
var db			*sql.DB
// Container for the JSON which will be returned by the HiFi API
// Works for both domains and users
type Container struct{
	Status string `json:"status"`
	Data struct{
		NumOnline int `json:"num_online"`
		Users []struct {
			Username string `json:"username"`
		}
	}
}
func errorHandling(err error){
	fmt.Println("ERROR:", err)
}
// Registers DB and does error handling
func openDB(){
	var err error
	db, err = sql.Open("mysql", "hifi:123456@hifi_stats")
    	if err != nil {
        	errorHandling(err)  // Just for example purpose. You should use proper error handling instead of panic
    	}
	err = db.Ping()
	if err != nil {
		errorHandling(err)
	}
}
func prepareInsertDB(){
	var err error
	insStatsDomains, err = db.Prepare("INSERT INTO stats_domains (time, domainCount) VALUES ( ?, ? )")
	if err != nil {
		errorHandling(err)
	}
	insStatsUsers, err = db.Prepare("INSERT INTO stats_users (time, userCount) VALUES ( ?, ? )")
	if err != nil{
		errorHandling(err)
	}
	insUsers, err = db.Prepare("INSERT INTO users (name, first_login, last_login) VALUES ( ?, ?, ? ) ON DUPLICATE KEY UPDATE last_login = ? ")
	if err != nil{
		errorHandling(err)
	}
}
// getHttpJson gets the body of the supplied link and returns it as byte.
// Also handles any errors and recovers from any errors, so that the program doesn't exit
func getHttpJson(link string) ([]byte){
	defer func() {
		if x := recover(); x != nil {
			fmt.Println("run time panic: %v", x)
		}
	}()
	res, err := http.Get(link)
	if err != nil {
		errorHandling(err)
	}
	data, err := ioutil.ReadAll(res.Body)
	res.Body.Close()
	if err != nil {
		errorHandling(err)
	}
	return data
}
// This function unmarshals the JSON and returns any errors
func  unmarshalJson(data []byte, container Container) (Container){
	err := json.Unmarshal(data, &container)
	if err != nil {
		errorHandling(err)
	}
	if container.Status != "success" {
		err = errors.New( "Source reached, but status != 'success'!")
		errorHandling(err)
	}
	return container
}
// This function parses the return of 'users' and prints out the time of capture + the user count
// When something went wrong in the whole process, container.Status != 'success', then the userCount will be returned -1
func parseUsers(){
	var container Container
	var userCount int
	timeCur := time.Now().UTC()
	data := getHttpJson(urlUsers)
	container = unmarshalJson(data, container)
	if container.Status != "success" {
		userCount = 0
	} else {
		userCount = len(container.Data.Users)
		for _,element := range container.Data.Users {
			//fmt.Println("Doing user: " + element.Username)
			_, err := insUsers.Exec(element.Username, timeCur, timeCur, timeCur)
			if err != nil {
				errorHandling(err)
			}
		}
	}
	_, err := insStatsUsers.Exec(timeCur, userCount)
	if err != nil {
		errorHandling(err)
	}
	//fmt.Printf("\n" + timeCur.Format(time.RFC3339) + "\nUsers Online: " + "%d", userCount)
}
// This function parses the return of 'domains' and prints out the time of capture + the domain count
// When something went wrong in the whole process, container.Status != 'success', then the domainCount will be returned -1
func parseDomains() {
	var container Container
	var domainCount int
	timeCur := time.Now().UTC()
	data := getHttpJson(urlStatsDomains)
	container = unmarshalJson(data, container)
	if container.Status != "success" {
		domainCount = 0
	} else {
		domainCount = container.Data.NumOnline
	}
	_, err := insStatsDomains.Exec(timeCur, domainCount)
	if err != nil {
			errorHandling(err)
	}
	//fmt.Printf("\n" + timeCur.Format(time.RFC3339) + "\nDomains Online: " + "%+v", domainCount)
}
func main() {
	openDB()
	// This has to be inside main(), else it'd close the db when it exits the func
	defer db.Close()
	prepareInsertDB()
	// Same as above
	defer insStatsDomains.Close()
	defer insStatsUsers.Close()
	//parseDomains()
	//parseUsers()
	c := cron.New()
	c.AddFunc("0 0     * * * *", parseDomains)
	c.AddFunc("0 0,10,20,30,40,50 * * * *", parseUsers)
	c.Start()
	select {}
}
